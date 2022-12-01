<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\ClearValuesRequest;

class GoogleSheet
{
    private $spreadSheetId;
    private $client;
    private $googleSheetService;

    public function __construct()
    {
        $this->spreadSheetId = config('google.google_sheet_id');

        $this->client = new Client();

        $this->client->setAuthConfig(storage_path('credentials.json'));

        $this->client->addScope("https://www.googleapis.com/auth/spreadsheets");

        $this->googleSheetService = new Sheets($this->client);
    }

    public function readGoogleSheet()
    {
    }

    public function getHeaders(array $data)
    {
        $headers = get_object_vars($data[0]);

        $arrayHeaders = array();
        foreach ($headers as $key => $value) {
            array_push($arrayHeaders, $key);
        }

        return $arrayHeaders;
    }

    public function getItem(object $data)
    {
        $itemData = array();
        foreach ($data as $key => $value) {
            array_push($itemData, $value);
        }

        return $itemData;
    }

    public function getData(array $data, bool $header)
    {
        $finalData = array();
        if ($header) {
            array_push($finalData, $this->getHeaders($data));
        }

        foreach ($data as $key => $value) {
            $itemData = $this->getItem($value);
            array_push($finalData, $itemData);
        }
        return $finalData;
    }

    public function saveRawDataToSheet(array $data, string $sheet, bool $clearSheet = true)
    {
        $dimensions = $this->getDimensions($this->spreadSheetId, $sheet);

        if ($clearSheet) {
            if (!$dimensions['error']) {
                $range = $sheet . "!A2:{$dimensions['colCount']}{$dimensions['rowCount']}";
                $clearBody = new ClearValuesRequest();
                $response = $this->googleSheetService->spreadsheets_values->clear($this->spreadSheetId, $range, $clearBody);
            }
        }

        $body = new ValueRange([
            'values' => $data
        ]);

        $params = [
            'valueInputOption' => 'USER_ENTERED'
        ];

        $range =  $sheet . "!A2";
        $result = $this->googleSheetService
            ->spreadsheets_values
            ->update($this->spreadSheetId, $range, $body, $params);

        return $result;
    }

    private function getDimensions($spreadSheetId, string $sheet)
    {
        $rowDimensions = $this->googleSheetService->spreadsheets_values->batchGet(
            $spreadSheetId,
            ['ranges' => $sheet . '!A:A', 'majorDimension' => 'COLUMNS']
        );

        //if data is present at nth row, it will return array till nth row
        //if all column values are empty, it returns null
        $rowMeta = $rowDimensions->getValueRanges()[0]->values;
        if (!$rowMeta) {
            return [
                'error' => true,
                'message' => 'missing row data'
            ];
        }

        $colDimensions = $this->googleSheetService->spreadsheets_values->batchGet(
            $spreadSheetId,
            ['ranges' => $sheet . '!1:1', 'majorDimension' => 'ROWS']
        );

        //if data is present at nth col, it will return array till nth col
        //if all column values are empty, it returns null
        $colMeta = $colDimensions->getValueRanges()[0]->values;
        if (!$colMeta) {
            return [
                'error' => true,
                'message' => 'missing row data'
            ];
        }

        return [
            'error' => false,
            'rowCount' => count($rowMeta[0]),
            'colCount' => $this->colLengthToColumnAddress(count($colMeta[0]))
        ];
    }

    private  function colLengthToColumnAddress($number)
    {
        if ($number <= 0) return null;

        $letter = '';
        while ($number > 0) {
            $temp = ($number - 1) % 26;
            $letter = chr($temp + 65) . $letter;
            $number = ($number - $temp - 1) / 26;
        }
        return $letter;
    }
}
