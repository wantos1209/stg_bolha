<?php

namespace App\Http\Controllers;

use App\Models\WinlossbetDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ReportdsController extends Controller
{
    public function index(Request $request)
    {
        $username = $request->query('username');
        $portfolio = $request->query('portfolio');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $data = $this->getDataWinLoss($request);
        $dataAmount = $this->getAmountUser($request);

        if ($data != null) {
            foreach ($data as &$element) {
                $username = $element['username'];

                $element['referral'] = 0;

                /* Amount */
                $matchingAmount = $dataAmount['username'] === $username ? $dataAmount['balance'] : 0;
                $element['amount'] = $matchingAmount;
            }
        } else if (!empty($dataAmount)) {
            $referral = 0;
            $data = [[
                'username' => $username,
                'amount' => $dataAmount['balance'],
                'referral' => $referral,
                'commission' => 0,
                'winlose' => 0
            ]];
        } else {
            $data = [];
        }
        if($username != ''){
            if(!empty($data)){
                $data[0]['username'] = $request->query('username');
            }
        }
        return view('reportds.index', [
            'title' => 'Report',
            'data' => $data,
            'totalnote' => 0,
            'username' => $username,
            'portfolio' => $portfolio,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    private function getDataWinLoss(Request $request)
    {
        $username = $request->query('username');
        $portfolio = $request->query('portfolio');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $data = [
            'username' => env('UNIX_CODE') . $username,
            'portfolio' => $portfolio,
            'startDate' => $startDate . 'T00:00:00.540Z',
            'endDate' => $endDate . 'T23:59:59.540Z',
            "companyKey" => env('COMPANY_KEY'),
            "serverId" =>  env('SERVERID')
        ];
        $apiUrl = env('BODOMAIN') . '/web-root/restricted/report/get-customer-report-by-win-lost-date.aspx';
        $response = Http::post($apiUrl, $data);
        $results = $response->json();
        
        if ($results["error"]["id"] == 0) {
            $result = $results["result"];
        } else {
            $result = [];
        }

        return $result;
    }

    private function getAmountUser(Request $request)
    {
        $username = $request->query('username');
        $data = [
            'Username' => env('UNIX_CODE') . $username,
            "CompanyKey" => env('COMPANY_KEY'),
            "ServerId" =>  env('SERVERID')
        ];
        $apiUrl = env('BODOMAIN') . '/web-root/restricted/player/get-player-balance.aspx';
        $response = Http::post($apiUrl, $data);
        $results = $response->json();

        if ($results["error"]["id"] == 0) {
            $result = $results;
        } else {
            $result = [];
        }

        return $result;
    }

    public function winlosematch(Request $request)
    {
        $username = $request->query('username');
        $portfolio = $request->query('portfolio');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $refNo = $request->query('refNo');
        $sportsType = $request->query('sportsType');
        $status = $request->query('status');

        if ($refNo != '') {
            $username = '';
            $startDate = '';
            $endDate = '';
        }

        $Message = '';
        $data = [];

        if ($refNo != '' && $portfolio != '') {
            $data = $this->requestApi('get-bet-list-by-refnos', [
                'refNos' => $refNo,
                'portfolio' => $portfolio,
                'companyKey' => env('COMPANY_KEY'),
                'language' => 'en',
                'serverId' => env('SERVERID')
            ]);
            if (!$data) {
                $data = [];
            } else {
                $data = $data["result"];
                // dd($data);
            }
        }

        if ($username != '' && $refNo == '') {
            $data = $this->requestApi('get-bet-list-by-modify-date', [
                'username' => env('UNIX_CODE') . $username,
                'portfolio' => $portfolio,
                'startDate' => $startDate . 'T00:00:00.540Z',
                'endDate' => $endDate . 'T23:59:59.540Z',
                'companyKey' => env('COMPANY_KEY'),
                'language' => 'en',
                'serverId' => env('SERVERID')
            ]);

            if ($data["error"]["id"] != 0) {
                $Message = "Username tidak terdaftar";
                $data = [];
            } else {
                $data = $data["result"];
            }
        }

        if ($portfolio == 'SportsBook') {
            $data_filter_sportsTypes = array_unique(array_column($data, 'sportsType'));
        } else if ($portfolio == 'VirtualSports' || $portfolio == 'Games') {
            $data_filter_sportsTypes = array_unique(array_column($data, 'productType'));
        } else {
            $data_filter_sportsTypes = [];
        }

        // dd($data);
        if ($sportsType != '') {
            $data = array_filter($data, function ($item) use ($portfolio, $sportsType) {
                if ($portfolio == 'SportsBook') {
                    return $item['sportsType'] === $sportsType;
                } else if ($portfolio == 'VirtualSports' || $portfolio == 'Games') {
                    return $item['productType'] === $sportsType;
                }
            });
        }

        if ($status != '') {
            $data = array_filter($data, function ($item) use ($status) {
                return $item['status'] === $status;
            });
        }

        $dataAmount = $this->getAmountUser($request);

        $data = collect($data)->map(function ($item) use ($dataAmount) {
            $username = $item['username'];
            $item['saldo'] = empty($dataAmount["balance"]) ? 0 : $dataAmount["balance"];
            return $item;
        })->toArray();


        return view('reportds.winlosematch', [
            'title' => 'Report',
            'totalnote' => 0,
            'data' => $data,
            'username' => $username,
            'portfolio' => $portfolio,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'refNo' => $refNo,
            'status' => $status,
            'sportsType' => $sportsType,
            'Message' => $Message,
            'data_filter_sportsTypes' => $data_filter_sportsTypes
        ]);
    }

    private function requestApi($endpoint, $data)
    {
        $url = env('BODOMAIN') . '/web-root/restricted/report/' . $endpoint . '.aspx';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json; charset=UTF-8',
        ])->post($url, $data);

        if ($response->successful()) {
            $responseData = $response->json();
        } else {
            // $statusCode = $response->status();
            // $errorMessage = $response->body();
            // $responseData = "Error: $statusCode - $errorMessage";
            $responseData = $response->json();
        }

        return $responseData;
    }

    public function memberstatement()
    {

        return view('reportds.memberstatement', [
            'title' => 'Report',
            'totalnote' => 0,
        ]);
    }

    public function index_towl(Request $request)
    {
        $portfolio = $request->input('portfolio');
        $gabungdari = $request->input('gabungdari') != null ? date('Y-m-d', strtotime($request->input('gabungdari'))) : '';
        $gabunghingga =  $request->input('gabunghingga') != null ? date('Y-m-d', strtotime($request->input('gabunghingga'))) : '';
        $username = $request->input('username');
        
        $results = $this->getDataBonus($portfolio, $gabungdari, $gabunghingga, $username);
        
        return view('reportds.to_wl', [
            'title' => 'TURN OVER & WINLOSE',
            'data' => $results,
            'totalnote' => 0,
            'portfolio' => $portfolio,
            'gabungdari' => $gabungdari,
            'gabunghingga' => $gabunghingga,
            'username' => $username,
            'totaluser' => $results->count(),
            'total_to' => $results->sum('totalstake'),
            'total_wl' => $results->sum('totalwinloss')
        ]);
    }

    private function getDataBonus($portfolio = null, $gabungdari = null, $gabunghingga = null, $username = null)
    {   
        if($gabungdari && $gabunghingga){
            $query = WinlossbetDay::query()
                ->when($portfolio, function ($query) use ($portfolio) {
                    return $query->where('portfolio', $portfolio);
                })
                ->when($gabungdari && $gabunghingga, function ($query) use ($gabungdari, $gabunghingga) {
                    return $query->whereBetween('created_at', [$gabungdari . ' 00:00:00', $gabunghingga . ' 23:59:59']);
                })
                ->when($username, function ($query) use ($username) {
                    return $query->where('username', $username);
                })
                ->select('username', DB::raw('SUM(stake) as totalstake'), DB::raw('SUM(winloss) as totalwinloss'))
                ->groupBy('username');

            $results = $query->get();
        }else{
            $results = collect();
        }

        return $results;
    }
}
