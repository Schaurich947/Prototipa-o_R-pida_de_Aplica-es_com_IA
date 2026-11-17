<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Issuer;

use DOMDocument;
use DOMElement;
use HotelFlex\Fiscal\Domain\HotelFiscalProfile;
use HotelFlex\Fiscal\Domain\Invoice;

/**
 * Simplified XML inspired by the national NFS-e layout (DPS). It is a simulation: not signed and not valid at the authority.
 * DOMDocument escapes every value, so taker names can never break the XML.
 */
final class InvoiceXmlBuilder
{
    public const SIMULATION_NOTICE = 'SIMULAÇÃO · Documento de teste · sem valor fiscal';

    public function build(Invoice $invoice, HotelFiscalProfile $issuer, ?string $accessKey): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $document->appendChild($document->createComment(' ' . self::SIMULATION_NOTICE . ' '));
        $root = $this->append($document, $document, 'DPS', null, ['versao' => 'simulacao', 'Id' => (string) $invoice->dpsId]);
        $this->append($document, $root, 'aviso', self::SIMULATION_NOTICE);
        $this->append($document, $root, 'tpAmb', '2');
        $this->append($document, $root, 'tipo', $invoice->kind->label());
        $this->append($document, $root, 'chaveAcesso', (string) $accessKey);
        $this->append($document, $root, 'serie', (string) $invoice->series);
        $this->append($document, $root, 'nDPS', (string) $invoice->number);
        $this->append($document, $root, 'dCompet', $invoice->competenceDate->format('Y-m-d'));
        $this->appendIssuer($document, $root, $issuer);
        $this->appendTaker($document, $root, $invoice);
        $this->appendValues($document, $root, $invoice, $issuer);

        return (string) $document->saveXML();
    }

    private function appendIssuer(DOMDocument $document, DOMElement $root, HotelFiscalProfile $issuer): void
    {
        $element = $this->append($document, $root, 'prest');
        $this->append($document, $element, 'CNPJ', $issuer->cnpj->value);
        $this->append($document, $element, 'IM', $issuer->municipalRegistration);
        $this->append($document, $element, 'xNome', $issuer->legalName);
        $this->append($document, $element, 'cMun', $issuer->municipalityCode);
        $this->append($document, $element, 'regTrib', $issuer->taxRegime->label());
    }

    private function appendTaker(DOMDocument $document, DOMElement $root, Invoice $invoice): void
    {
        $taker = $invoice->taker;
        $element = $this->append($document, $root, 'toma');
        if ($taker->document !== null) {
            $this->append($document, $element, strtoupper($taker->document->type->value === 'estrangeiro' ? 'NIF' : $taker->document->type->value), $taker->document->value);
        }
        $this->append($document, $element, 'xNome', $taker->name);
        if ($taker->stateRegistration !== null) {
            $this->append($document, $element, 'IE', $taker->stateRegistration);
        }
        if ($taker->address !== null) {
            $this->append($document, $element, 'end', $taker->address);
        }
    }

    private function appendValues(DOMDocument $document, DOMElement $root, Invoice $invoice, HotelFiscalProfile $issuer): void
    {
        $taxes = $invoice->taxes;
        $service = $this->append($document, $root, 'serv');
        $this->append($document, $service, 'cTribNac', $issuer->serviceCode);
        $this->append($document, $service, 'xDescServ', $invoice->description);
        $values = $this->append($document, $root, 'valores');
        $this->append($document, $values, 'vServ', self::toDecimal($invoice->amountCents));
        $this->append($document, $values, 'vISSQN', self::toDecimal($taxes->issCents));
        $this->append($document, $values, 'tpRetISSQN', $taxes->isIssWithheld ? '2' : '1');
        $this->append($document, $values, 'vICMS', self::toDecimal($taxes->icmsCents));
        $reform = $this->append($document, $values, 'IBSCBS', null, ['informativo' => $taxes->isReformInformative ? 'sim' : 'nao']);
        $this->append($document, $reform, 'vCBS', self::toDecimal($taxes->cbsCents));
        $this->append($document, $reform, 'vIBS', self::toDecimal($taxes->ibsCents));
        $this->append($document, $values, 'vTotTribAprox', self::toDecimal($taxes->approximateTotalCents()));
    }

    /** @param array<string, string> $attributes */
    private function append(DOMDocument $document, DOMDocument|DOMElement $parent, string $name, ?string $text = null, array $attributes = []): DOMElement
    {
        $element = $document->createElement($name);
        if ($text !== null) {
            $element->appendChild($document->createTextNode($text));
        }
        foreach ($attributes as $attribute => $value) {
            $element->setAttribute($attribute, $value);
        }
        $parent->appendChild($element);

        return $element;
    }

    private static function toDecimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
