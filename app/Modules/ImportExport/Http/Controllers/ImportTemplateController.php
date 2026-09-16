<?php

namespace App\Modules\ImportExport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ImportExport\Enums\ImportKind;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportTemplateController extends Controller
{
    public function __invoke(ImportKind $kind): StreamedResponse
    {
        $headers = $kind === ImportKind::Financial
            ? ['Data', 'Tipo', 'Descrição', 'Conta', 'Conta destino', 'Categoria', 'Valor', 'Moeda']
            : ['Data', 'Tipo', 'Ativo', 'Mercado', 'Corretora', 'Quantidade', 'Preço unitário', 'Taxas', 'Valor bruto', 'Valor líquido', 'Proporção origem', 'Proporção destino', 'Observação'];

        return response()->streamDownload(function () use ($headers): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers, ';', '"', '', "\r\n");
            fclose($stream);
        }, "modelo-{$kind->value}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
