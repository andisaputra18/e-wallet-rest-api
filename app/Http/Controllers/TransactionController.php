<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;

class TransactionController extends BaseController
{
    public function top_up(Request $request)
    {
        $user_id = $request->user_id;

        $input = $request->all();
        $input['date'] = date('Y-m-d');
        Transaction::create($input);

        // Proccess top up
        $this->top_up_proccess($user_id, $request->amount);

        return response()->json(["message" => "Top-Up successfully"]);
    }

    public function transfer(Request $request)
    {
        $user_id = $request->user_id;
        $amount = $request->amount;

        // Check user
        $user = User::find($request->to_user_id);
        if (is_null($user)) {
            return $this->sendError('Transfer Failed!. User unregistered on system.', 422);
        }
        // Check balance
        $check_balance = Balance::where("user_id", $user_id)->first();
        if ($check_balance->nominal > 0) {
            if ($check_balance->nominal < $amount) {
                return $this->sendError('Transfer Failed!. Your balance is not enough.', 422);
            }
        } else {
            return $this->sendError('Transfer Failed!. You dont have a balance.', 422);
        }

        $transaction = new Transaction;
        $transaction->user_id = $request->user_id;
        $transaction->type_id = $request->type_id;
        $transaction->amount = $request->amount;
        $transaction->date = date('Y-m-d');
        $transaction->save();

        $transaction_id = $transaction->id;

        $to_user_id = $request->to_user_id;
        $transfer = new Transfer;
        $transfer->transaction_id = $transaction_id;
        $transfer->to_user_id = $to_user_id;
        $transfer->save();

        // Process transfer
        $this->top_up_proccess($to_user_id, $amount);

        $balance_update = Balance::where("user_id", $user_id)->first();
        $balance_update->nominal = $balance_update->nominal - $amount;
        $balance_update->save();

        return response()->json(["message" => "Your transfer is successfully"]);
    }

    public function withdraw(Request $request)
    {
        $user_id = $request->user_id;
        $amount = $request->amount;
        $account_number = $request->account_number;
        unset($request->account_number);

        $balance = Balance::where("user_id", $user_id)->first();
        if ($balance->nominal > 0) {
            if ($balance->nominal < $amount) {
                return $this->sendError('Withdraw Failed!. Your balance is not enough.', 422);
            }
        } else {
            return $this->sendError('Withdraw Failed!. Your balance is not enough.', 422);
        }

        $account = Account::where('account_number', $account_number)->first();
        if (is_null($account)) {
            return $this->sendError('Withdraw Failed!. Your account is not found', 422);
        }

        // Insert transaction
        $input = $request->all();
        $input['date'] = date('Y-m-d');
        $transaction = Transaction::create($input);
        $transaction_id = $transaction->id;

        // Insert withdraw
        $withdraw = new Withdraw;
        $withdraw->transaction_id = $transaction_id;
        $withdraw->account_id = $account->id;
        $withdraw->save();

        // Proccess update balance
        $balance->nominal = $balance->nominal - $amount;
        $balance->save();

        return response()->json(["message" => "Your withdraw is successfully"]);
    }

    public function top_up_proccess($user_id, $amount)
    {
        $check_balance = Balance::where("user_id", $user_id)->first();
        if (!is_null($check_balance)) {
            $check_balance->nominal = $check_balance->nominal + $amount;
            $check_balance->save();
        } else {
            $balance = new Balance;
            $balance->user_id = $user_id;
            $balance->nominal = $amount;
            $balance->save();
        }
    }
}
