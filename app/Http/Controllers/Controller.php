<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    protected $connDekstop;

    public function __construct()
    {
        $this->connDekstop = DB::connection('milkyverse_dekstop');
    }

    public function getUserDataAndTransactionSummary(Request $request)
    {
        Log::info("Memulai konfigurasi database");
        $ipConfigOutput = shell_exec('ipconfig');
        preg_match('/IPv4\s*Address.*?:\s*(\d+\.\d+\.\d+\.\d+)/', $ipConfigOutput, $matches);

        $resolvedIP = isset($matches[1]) ? '172.26.8.250' : $matches[1];
        Log::info("Resolved IP: " . $resolvedIP);

        Config::set('database.connections.milkyverse_dekstop.host', $resolvedIP);
        Config::set('database.connections.milkyverse_dekstop.port', '8191');
        Config::set('database.connections.milkyverse_dekstop.database', 'milkyverse');
        Config::set('database.connections.milkyverse_dekstop.username', 'mkv_user');
        Config::set('database.connections.milkyverse_dekstop.password', 'mkvusr@123');

        DB::purge('milkyverse_dekstop');

        try {
            DB::connection('milkyverse_dekstop')->getPdo();
            Log::info("Koneksi ke database berhasil!");

            $no_nfc = $request->input('no_nfc');

            $user = DB::connection('milkyverse_dekstop')->table('mkv.capit_master_kartu')
                ->where('no_nfc', $no_nfc)
                ->get();

            if ($user->isNotEmpty()) {
                $transactions = DB::connection('milkyverse_dekstop')->table('mkv.capit_summary_transaksi')
                    ->select('usr_cr', DB::raw('COUNT(0) as count'), 'date_upd', 'no_nfc', 'tipe')
                    ->where('no_nfc', $no_nfc)
                    ->where('tipe', '<>', '1')
                    ->groupBy('usr_cr', 'date_upd', 'no_nfc', 'tipe')
                    ->get();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Data ditemukan',
                    'user' => $user,
                    'transactions' => $transactions
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Data pengguna tidak ditemukan',
                'user' => null,
                'transactions' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Koneksi database gagal: " . $e->getMessage());
            return response()->json(['error' => 'Koneksi database gagal!'], 500);
        }
    }

}
