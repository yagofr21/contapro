<?php

namespace App\Modules\ImportExport\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class CsvReader
{
    /** @return array{headers: list<string>, rows: list<array<string, string>>, delimiter: string} */
    public function read(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getPathname());

        if ($contents === false || $contents === '') {
            throw ValidationException::withMessages(['file' => 'O arquivo CSV esta vazio.']);
        }

        if (str_contains($contents, "\0")) {
            throw ValidationException::withMessages(['file' => 'O arquivo CSV contem dados binarios invalidos.']);
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $firstLine = strtok($contents, "\r\n");
        $delimiter = $this->detectDelimiter($firstLine === false ? '' : $firstLine);
        $stream = fopen('php://temp', 'r+b');

        if ($stream === false) {
            throw ValidationException::withMessages(['file' => 'Nao foi possivel processar o arquivo.']);
        }

        fwrite($stream, $contents);
        rewind($stream);
        $headers = fgetcsv($stream, null, $delimiter, '"', '');

        if ($headers === false || count($headers) < 2 || count($headers) > 100) {
            fclose($stream);
            throw ValidationException::withMessages(['file' => 'O cabecalho do CSV e invalido.']);
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);

        if (in_array('', $headers, true) || count(array_unique($headers)) !== count($headers)) {
            fclose($stream);
            throw ValidationException::withMessages(['file' => 'O CSV possui cabecalhos vazios ou duplicados.']);
        }

        $rows = [];

        while (($values = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            if (count($values) === 1 && trim((string) $values[0]) === '') {
                continue;
            }

            if (count($values) !== count($headers)) {
                fclose($stream);
                throw ValidationException::withMessages(['file' => 'O CSV possui linhas com quantidade incorreta de colunas.']);
            }

            $values = array_map(fn ($value) => trim((string) $value), $values);

            if (collect($values)->contains(fn (string $value) => mb_strlen($value) > 10000)) {
                fclose($stream);
                throw ValidationException::withMessages(['file' => 'O CSV possui uma celula maior que 10.000 caracteres.']);
            }

            /** @var array<string, string> $row */
            $row = array_combine($headers, $values);
            $rows[] = $row;

            if (count($rows) > 1000) {
                fclose($stream);
                throw ValidationException::withMessages(['file' => 'O CSV pode conter no maximo 1.000 linhas.']);
            }
        }

        fclose($stream);

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'O CSV nao possui linhas para importar.']);
        }

        return ['headers' => array_values($headers), 'rows' => $rows, 'delimiter' => $delimiter];
    }

    private function detectDelimiter(string $line): string
    {
        $counts = [];

        foreach ([';', ',', "\t"] as $delimiter) {
            $counts[$delimiter] = count(str_getcsv($line, $delimiter, '"', ''));
        }

        arsort($counts);
        $delimiter = (string) array_key_first($counts);

        if (($counts[$delimiter] ?? 0) < 2) {
            throw ValidationException::withMessages(['file' => 'Nao foi possivel identificar o separador do CSV.']);
        }

        return $delimiter;
    }
}
