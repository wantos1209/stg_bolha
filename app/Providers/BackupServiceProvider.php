<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Xdpwd;
use App\Models\UserAccess;
use App\Models\Outstanding;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Authenticated;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Authenticated::class, function ($event) {
            // $data = $event->user;
            View::share('dataCount', $this->getDataCount());
            // $this->gate();
        });
        // $user = Auth::user();
        // dd($user);
        // $this->userAndUserAccess($user);

        Http::macro('withTokenHeader', function () {
            return Http::withHeaders([
                'x-customblhdrs' => env('XCUSTOMBLHDRS'),
            ]);
        });
        // Gate::define('deposit', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['deposit'] === 1;
        // });
        // Gate::define('withdraw', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['withdraw'] === 1;
        // });
        // Gate::define('manual_transaction', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['manual_transaction'] === 1;
        // });
        // Gate::define('history_coin', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['history_coin'] === 1;
        // });
        // Gate::define('member_list', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['member_list'] === 1;
        // });
        // Gate::define('referral', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['referral'] === 1;
        // });
        // Gate::define('history_game', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['history_game'] === 1;
        // });
        // Gate::define('member_outstanding', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['member_outstanding'] === 1;
        // });
        // Gate::define('history_transaction', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['history_transaction'] === 1;
        // });
        // Gate::define('cashback_rollingan', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['cashback_rollingan'] === 1;
        // });
        // Gate::define('report', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['report'] === 1;
        // });
        // Gate::define('bank', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['bank'] === 1;
        // });
        // Gate::define('memo', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['memo'] === 1;
        // });
        // Gate::define('agent', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['agent'] === 1;
        // });
        // Gate::define('analytic', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['analytic'] === 1;
        // });
        // Gate::define('content', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['content'] === 1;
        // });
        // Gate::define('apk_setting', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['apk_setting'] === 1;
        // });
        // Gate::define('memo_other', function(User $user){
        //     $user = $this->userAndUserAccess();
        //     return $user['user_access']['memo_other'] === 1;
        // });

        // View::share('dataCount', [
        //     "countDP" => 2,
        //     "countWD" => 3,
        //     "countOuts" => 4,
        //     "countMemo" => 5,
        // ]);

        // if ($this->app->runningInConsole() || $this->app->environment('testing')) {
        //     return;
        // }

        // $currentRoute = Route::getCurrentRoute();
        // $currentUrl = $currentRoute ? $currentRoute->uri() : '';

        // if (!Str::startsWith($currentUrl, 'api/')) {
        //     View::share('dataCount', $this->getDataCount());
        // }
    }

    private function getDataCount()
    {
        $countDataDP = Xdpwd::where('jenis', 'DP')->where('status', 0)->count();
        $countDataWD = Xdpwd::where('jenis', 'WD')->where('status', 0)->count();

        $dataOuts = Outstanding::get();
        $dataOuts = $dataOuts->groupBy('username')->map(function ($group) {
            $totalAmount = $group->sum('amount');
            $count = $group->count();
            return [
                'username' => $group->first()['username'],
                'totalAmount' => $totalAmount,
                'count' => $count,
            ];
        })->count();

        $responseMemo = Http::withHeaders([
            'x-customblhdrs' => env('XCUSTOMBLHDRS')
        ])->get(env('DOMAIN') . '/memo');
        $resultMemo = $responseMemo->json();
        if ($resultMemo['status'] == 'success') {
            $countMemo = count($resultMemo['data']);
        } else {
            $countMemo = 0;
        }


        return [
            'countDP' => $countDataDP,
            'countWD' => $countDataWD,
            'countOuts' => $dataOuts,
            // 'countMemo' => $countMemo
            'countMemo' => 0
        ];
    }
    public function userAndUserAccess()
    {
        $user = auth()->user();
        $userWithAccess = User::with('userAccess')->find($user->id);
        // $userWithAccess = User::with('userAccess')->find($user->id); 
        // userAccess di atas adalah penghubung ke method userAccess di model User.php
        // Cara bacanya User yang memiliki hubungan ke model UserAccess dengan name_access yang serupa dengan divisi milik model User
        // Maka temukan ID nya si auth user dan 
        $result = $userWithAccess->toArray();
        if ($result['name'] === 'admin L21' && $result['username'] === env('XUSRADXE') && $result['divisi'] === 'superadmin') {
            $result['user_access'] = [
                'deposit' => 1,
                'withdraw' => 1,
                'manual_transaction' => 1,
                'history_coin' => 1,
                'member_list' => 1,
                'history_transaction' => 1,
                'referral' => 1,
                'history_game' => 1,
                'member_outstanding' => 1,
                'cashback_rollingan' => 1,
                'report' => 1,
                'bank' => 1,
                'memo' => 1,
                'agent' => 1,
                'analytic' => 1,
                'content' => 1,
                'apk_setting' => 1,
                'memo_other' => 1,
            ];
        } else {
            // Fetch user access from the relationship
            $access = $userWithAccess->userAccess->pluck('name_access')->toArray();
        }

        return $result;
    }
    // public function userAndUserAccess()
    // {
    //     $user = auth()->user();
    //     $userWithAccess = User::with('userAccess')->find($user->id); 
    //     if ($userWithAccess) {
    //         $result = $userWithAccess->toArray();
    //         if ($result['name'] === 'admin L21' && $result['username'] === 'gl0b4l#21' && $result['divisi'] === 'superadmin') {
    //             $result['user_access'] = [
    //                 'deposit' => 1,
    //                 'withdraw' => 1,
    //                 'manual_transaction' => 1,
    //                 'history_coin' => 1,
    //                 'member_list' => 1,
    //                 'history_transaction' => 1,
    //                 'referral' => 1,
    //                 'history_game' => 1,
    //                 'member_outstanding' => 1,
    //                 'cashback_rollingan' => 1,
    //                 'report' => 1,
    //                 'bank' => 1,
    //                 'memo' => 1,
    //                 'agent' => 1,
    //                 'analytic' => 1,
    //                 'content' => 1,
    //                 'apk_setting' => 1,
    //                 'memo_other' => 1,
    //             ];
    //         } else {
    //             $result = $userWithAccess->userAccess->pluck('name_access')->toArray();
    //         }
    //     }
    //     return $result;
    // }
    private function gate()
    {
        Gate::define('deposit', function (User $user) {
            $user = $this->userAndUserAccess();
            return ($user['user_access']['deposit']) === 1;
        });

        Gate::define('deposit', function (User $user) {
            return isset($user['user_access']['deposit']) === 1;
        });
        Gate::define('withdraw', function (User $user) {
            return isset($user['user_access']['withdraw']) === 1;
        });
        Gate::define('manual_transaction', function (User $user) {
            return isset($user['user_access']['manual_transaction']) === 1;
        });
        Gate::define('history_coin', function (User $user) {
            return isset($user['user_access']['history_coin']) === 1;
        });
        Gate::define('member_list', function (User $user) {
            return isset($user['user_access']['member_list']) === 1;
        });
        Gate::define('referral', function (User $user) {
            return isset($user['user_access']['referral']) === 1;
        });
        Gate::define('history_game', function (User $user) {
            return isset($user['user_access']['history_game']) === 1;
        });
        Gate::define('member_outstanding', function (User $user) {
            return isset($user['user_access']['member_outstanding']) === 1;
        });
        Gate::define('history_transaction', function (User $user) {
            return isset($user['user_access']['history_transaction']) === 1;
        });
        Gate::define('cashback_rollingan', function (User $user) {
            return isset($user['user_access']['cashback_rollingan']) === 1;
        });
        Gate::define('report', function (User $user) {
            return isset($user['user_access']['report']) === 1;
        });
        Gate::define('bank', function (User $user) {
            return isset($user['user_access']['bank']) === 1;
        });
        Gate::define('memo', function (User $user) {
            return isset($user['user_access']['memo']) === 1;
        });
        Gate::define('agent', function (User $user) {
            return isset($user['user_access']['agent']) === 1;
        });
        Gate::define('analytic', function (User $user) {
            return isset($user['user_access']['analytic']) === 1;
        });
        Gate::define('content', function (User $user) {
            return isset($user['user_access']['content']) === 1;
        });
        Gate::define('apk_setting', function (User $user) {
            return isset($user['user_access']['apk_setting']) === 1;
        });
        Gate::define('memo_other', function (User $user) {
            return isset($user['user_access']['memo_other']) === 1;
        });
    }

    // Define gates using a loop
    // foreach ($result['user_access'] as $gate => $value) {
    //     Gate::define($gate, function (User $user) use ($gate) {
    //         dd
    //         $userAccess = $user['user_access'] ?? [];
    //         return isset($userAccess[$gate]) && $userAccess[$gate] === 1;
    //     });
    // }



}
