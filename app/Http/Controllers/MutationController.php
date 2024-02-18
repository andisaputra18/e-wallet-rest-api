<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Http\Controllers\BaseController as BaseController;

class MutationController extends BaseController
{
    public function mutation(Request $request, $user_id)
    {
        $type = $request->input('jenis_transaksi');
        switch ($type) {
            case 'masuk':
                $data = $this->income($request, $user_id);
                break;
            case 'keluar':
                $data = $this->outcome($request, $user_id);
                break;
            default:
                break;
        }

        return $this->sendResponse($data, 'Data load successfully.');
    }

    public function income($request, $user_id)
    {
        $from = $request->input('tanggal_awal');
        $to = $request->input('tanggal_akhir');

        if ($from > $to) {
            return $this->sendError('Transfer Failed!. Periode date is not valid.', 422);
        }
        $top_up = Transaction::selectRaw("amount, types.name, date")
            ->join("types", 'types.id', '=', 'transactions.type_id')
            ->where(["user_id" => $user_id, "type_id" => 1])
            ->whereBetween('date', [$from, $to])
            ->get();

        $transfer = Transfer::selectRaw("amount, types.name, date")
            ->join("transactions", "transactions.id", "=", "transfers.transaction_id")
            ->join("types", 'types.id', '=', 'transactions.type_id')
            ->where(["to_user_id" => $user_id])
            ->whereBetween('date', [$from, $to])
            ->get();

        $data = Arr::collapse([$top_up, $transfer]);
        return $data;
    }

    public function outcome($request, $user_id)
    {
        $from = $request->input('tanggal_awal');
        $to = $request->input('tanggal_akhir');

        if ($from > $to) {
            return $this->sendError('Transfer Failed!. Periode date is not valid.', 422);
        }
        $outcome = Transaction::selectRaw("amount, types.name, date")
            ->join("types", 'types.id', '=', 'transactions.type_id')
            ->where(["user_id" => $user_id, "type_id" => 2])
            ->orWhere("type_id", 3)
            ->get();

        $data = $outcome;
        return $data;
    }
}
