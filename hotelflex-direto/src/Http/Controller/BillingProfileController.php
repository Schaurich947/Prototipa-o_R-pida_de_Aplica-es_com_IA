<?php

declare(strict_types=1);

namespace HotelFlex\Http\Controller;

use HotelFlex\Domain\DomainException;
use HotelFlex\Fiscal\Domain\BillingKind;
use HotelFlex\Fiscal\Service\BillingProfileService;
use HotelFlex\Http\Auth;
use HotelFlex\Http\HttpRequest;
use HotelFlex\Http\SafeReturnPath;
use HotelFlex\Http\Session;
use HotelFlex\View\View;

/** The customer's invoice data ("dados para a nota fiscal"). */
final class BillingProfileController
{
    public const PATH = '/faturamento';
    private const FORM_FIELDS = ['kind', 'name', 'document', 'email', 'state_registration', 'country', 'street', 'number', 'district', 'city', 'state', 'postal_code', 'municipality_code'];

    public function __construct(
        private readonly Auth $auth,
        private readonly Session $session,
        private readonly BillingProfileService $billing,
        private readonly ActionRunner $actions,
        private readonly View $view,
    ) {
    }

    public function show(HttpRequest $request): void
    {
        $actor = $this->auth->requireUser();
        $cnpj = $request->getQueryString('consultar_cnpj');
        [$company, $lookupMessage] = $cnpj !== '' ? $this->lookUp($cnpj) : [null, null];

        $this->view->render('billing-profiles', [
            'pageTitle' => 'Dados para nota fiscal',
            'profiles' => $this->billing->listFor($actor),
            'returnTo' => SafeReturnPath::sanitize($request->getQueryString('voltar')),
            'old' => $cnpj !== '' ? ['kind' => BillingKind::Company->value, 'document' => $cnpj] : $this->session->pullOldInput(),
            'company' => $company,
            'lookupMessage' => $lookupMessage,
        ]);
    }

    public function create(HttpRequest $request): never
    {
        $actor = $this->auth->requireUser();
        $returnTo = SafeReturnPath::sanitize($request->getBodyString('return_to'));
        $input = array_map(static fn (string $field) => $request->getBodyString($field), array_combine(self::FORM_FIELDS, self::FORM_FIELDS));
        $this->actions->run(function () use ($actor, $input, $returnTo): string {
            $this->billing->create($actor, $input);

            return $returnTo ?? self::PATH;
        }, 'Dados para nota fiscal salvos.', self::PATH . ($returnTo !== null ? '?voltar=' . rawurlencode($returnTo) : ''), $input);
    }

    /** @param array<string, int> $params */
    public function archive(HttpRequest $request, array $params): never
    {
        $actor = $this->auth->requireUser();
        $this->actions->run(fn () => $this->billing->archive($actor, $params['id']), 'Cadastro removido. Notas já emitidas não mudam.', self::PATH);
    }

    /** @return array{0: ?\HotelFlex\Fiscal\Domain\CompanyRecord, 1: ?string} */
    private function lookUp(string $cnpj): array
    {
        try {
            $company = $this->billing->lookUpCompany($cnpj);

            return [$company, $company === null ? 'Consulta indisponível agora. Preencha os dados manualmente; eles serão conferidos na emissão.' : null];
        } catch (DomainException $error) {
            return [null, $error->getMessage()];
        }
    }
}
