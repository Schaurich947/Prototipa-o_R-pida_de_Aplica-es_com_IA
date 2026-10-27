<?php

declare(strict_types=1);

namespace HotelFlex\Repository;

use DateTimeImmutable;
use HotelFlex\Domain\RequestStatus;
use HotelFlex\Domain\RequestType;
use HotelFlex\Domain\ServiceRequest;
use PDO;

final class ServiceRequestRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return list<ServiceRequest> */
    public function findAll(): array
    {
        $statement = $this->connection->query('SELECT * FROM service_requests ORDER BY created_at DESC, id DESC');

        return array_map(ServiceRequest::fromRow(...), $statement->fetchAll());
    }

    /** @return list<ServiceRequest> */
    public function findByReservationId(int $reservationId): array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM service_requests WHERE reservation_id = :reservation_id ORDER BY created_at DESC, id DESC',
        );
        $statement->execute(['reservation_id' => $reservationId]);

        return array_map(ServiceRequest::fromRow(...), $statement->fetchAll());
    }

    public function findById(int $requestId): ?ServiceRequest
    {
        $statement = $this->connection->prepare('SELECT * FROM service_requests WHERE id = :id');
        $statement->execute(['id' => $requestId]);
        $row = $statement->fetch();

        return $row ? ServiceRequest::fromRow($row) : null;
    }

    public function hasPending(int $reservationId, RequestType $type): bool
    {
        $statement = $this->connection->prepare(
            "SELECT 1 FROM service_requests WHERE reservation_id = :reservation_id AND type = :type AND status = 'pending' LIMIT 1",
        );
        $statement->execute(['reservation_id' => $reservationId, 'type' => $type->value]);

        return $statement->fetchColumn() !== false;
    }

    public function create(int $reservationId, RequestType $type, int $extraHours, DateTimeImmutable $createdAt): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO service_requests (reservation_id, type, extra_hours, status, created_at)
             VALUES (:reservation_id, :type, :extra_hours, :status, :created_at)',
        );
        $statement->execute([
            'reservation_id' => $reservationId,
            'type' => $type->value,
            'extra_hours' => $extraHours,
            'status' => RequestStatus::Pending->value,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    /** Only resolves requests that are still pending, so a double click cannot approve twice. */
    public function resolve(int $requestId, RequestStatus $status, DateTimeImmutable $resolvedAt): bool
    {
        $statement = $this->connection->prepare(
            "UPDATE service_requests SET status = :status, resolved_at = :resolved_at WHERE id = :id AND status = 'pending'",
        );
        $statement->execute(['status' => $status->value, 'resolved_at' => $resolvedAt->format('Y-m-d H:i:s'), 'id' => $requestId]);

        return $statement->rowCount() === 1;
    }
}
