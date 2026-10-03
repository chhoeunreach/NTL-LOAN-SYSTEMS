<?php

namespace Modules\LoanManagement\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LoanAbaPaywayController extends Controller
{
    use ApiResponseTrait;

    protected string $conn = 'mysql_loan';

    public function create(Request $request)
    {
        $data = $request->validate([
            'loan_id' => 'nullable|integer',
            'payment_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:10',
            'payment_option' => 'nullable|string|max:50',
        ]);

        $ref = 'ABA-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        $payload = [
            'loan_id' => $data['loan_id'] ?? null,
            'payment_id' => $data['payment_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'merchant_ref_no' => $ref,
            'payment_option' => $data['payment_option'] ?? 'khqr',
            'amount' => (float) $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'status' => 'pending',
            'request_payload' => json_encode($data),
            'response_payload' => json_encode(['checkout_url' => url('/loan-management/payway/' . $ref)]),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::connection($this->conn)->table('loan_aba_payway_transactions')
            ->insertGetId($this->safeColumns('loan_aba_payway_transactions', $payload));

        if ($request->wantsJson()) {
            return $this->ok('ABA PayWay transaction created', [
                'id' => $id,
                'merchant_ref_no' => $ref,
                'status' => 'pending',
                'checkout_url' => url('/loan-management/payway/' . $ref),
                'khqr' => 'KHQR-' . $ref,
            ]);
        }

        return redirect()->route('loan-management.payway.checkout', $ref);
    }

    public function checkout(string $ref)
    {
        $transaction = DB::connection($this->conn)->table('loan_aba_payway_transactions')
            ->where('merchant_ref_no', $ref)
            ->first();

        abort_if(! $transaction, 404, 'Transaction not found');

        $loan = null;
        $customer = null;

        if ($transaction->loan_id) {
            $loan = DB::connection($this->conn)->table('loans')->where('id', $transaction->loan_id)->first();
        }

        if ($transaction->customer_id) {
            $customer = DB::connection($this->conn)->table('loan_customers')->where('id', $transaction->customer_id)->first();
        } elseif ($loan && ! empty($loan->customer_id)) {
            $customer = DB::connection($this->conn)->table('loan_customers')->where('id', $loan->customer_id)->first();
        }

        // Generate standardized Bakong KHQR payload format
        $khqrPayload = "00020101021229370016abaa0000000000010108" . str_pad($transaction->merchant_ref_no, 16, '0') . "520459995303" . ($transaction->currency === 'KHR' ? '116' : '840') . "540" . strlen((string) $transaction->amount) . $transaction->amount . "5802KH5911NTL FINANCE6010PHNOM PENH6304ABCD";

        return view('loanmanagement::payway.checkout', compact('transaction', 'loan', 'customer', 'khqrPayload'));
    }

    public function checkStatus(Request $request): JsonResponse
    {
        $ref = $request->input('merchant_ref_no');
        if (! $ref) {
            return response()->json(['success' => false, 'message' => 'Reference required'], 422);
        }

        $row = DB::connection($this->conn)->table('loan_aba_payway_transactions')
            ->where('merchant_ref_no', $ref)
            ->first();

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        return response()->json([
            'success' => true,
            'id' => (int) $row->id,
            'merchant_ref_no' => (string) $row->merchant_ref_no,
            'status' => (string) $row->status,
            'amount' => (float) $row->amount,
            'currency' => (string) ($row->currency ?? 'USD'),
            'verified_at' => $row->verified_at,
            'redirect_url' => $row->status === 'approved' ? ($row->loan_id ? route('loan-management.loans.view', $row->loan_id) : route('loan-management.payments.index')) : null,
        ]);
    }

    public function simulateSuccess(string $ref)
    {
        $transaction = DB::connection($this->conn)->table('loan_aba_payway_transactions')
            ->where('merchant_ref_no', $ref)
            ->first();

        abort_if(! $transaction, 404);

        if ($transaction->status === 'approved') {
            return redirect()->route('loan-management.payway.checkout', $ref);
        }

        DB::connection($this->conn)->transaction(function () use ($transaction, $ref) {
            $amount = (float) $transaction->amount;
            $loanId = $transaction->loan_id;

            // 1. Mark transaction approved
            DB::connection($this->conn)->table('loan_aba_payway_transactions')
                ->where('id', $transaction->id)
                ->update([
                    'status' => 'approved',
                    'aba_transaction_id' => 'ABA-TXN-' . strtoupper(Str::random(8)),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            // 2. Create LoanPayment record
            $paymentId = null;
            if (Schema::connection($this->conn)->hasTable('loan_payments')) {
                $paymentPayload = [
                    'payment_ref_no' => $ref,
                    'loan_id' => $loanId ?: 1,
                    'customer_id' => $transaction->customer_id ?: 1,
                    'schedule_id' => null,
                    'received_by' => auth()->id() ?: 1,
                    'received_by_name_snapshot' => auth()->check() ? auth()->user()->name : 'ABA PayWay Gateway',
                    'channel' => 'aba_payway',
                    'amount' => $amount,
                    'paid_at' => now(),
                    'status' => 'confirmed',
                    'note' => 'Payment received via ABA PayWay / Bakong KHQR. Ref: ' . $ref,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $paymentId = DB::connection($this->conn)->table('loan_payments')
                    ->insertGetId($this->safeColumns('loan_payments', $paymentPayload));
            }

            // 3. Allocate to schedules (Penalty -> Interest -> Principal)
            if ($loanId && Schema::connection($this->conn)->hasTable('loan_payment_schedules')) {
                $openSchedules = DB::connection($this->conn)->table('loan_payment_schedules')
                    ->where('loan_id', $loanId)
                    ->where('status', '!=', 'paid')
                    ->orderBy('installment_no')
                    ->get();

                $remainingMoney = $amount;

                foreach ($openSchedules as $sched) {
                    if ($remainingMoney <= 0) break;

                    $schedDue = (float) ($sched->amount_balance ?? ($sched->amount_due - ($sched->amount_paid ?? 0)));
                    if ($schedDue <= 0) continue;

                    $payToThis = min($remainingMoney, $schedDue);
                    $newPaid = round(((float) ($sched->amount_paid ?? 0)) + $payToThis, 2);
                    $newBalance = max(0, round($schedDue - $payToThis, 2));
                    $newStatus = $newBalance <= 0.01 ? 'paid' : 'partial';

                    DB::connection($this->conn)->table('loan_payment_schedules')
                        ->where('id', $sched->id)
                        ->update([
                            'amount_paid' => $newPaid,
                            'amount_balance' => $newBalance,
                            'status' => $newStatus,
                            'paid_at' => $newBalance <= 0.01 ? now() : null,
                            'updated_at' => now(),
                        ]);

                    $remainingMoney -= $payToThis;
                }

                // 4. Update Loan balance
                $updatedSchedules = DB::connection($this->conn)->table('loan_payment_schedules')
                    ->where('loan_id', $loanId)
                    ->get();

                $totalPaid = (float) $updatedSchedules->sum('amount_paid');
                $totalBalance = (float) $updatedSchedules->sum('amount_balance');

                DB::connection($this->conn)->table('loans')
                    ->where('id', $loanId)
                    ->update([
                        'paid_amount' => $totalPaid,
                        'balance_amount' => $totalBalance,
                        'status' => $totalBalance <= 0.01 ? 'completed' : 'active',
                        'updated_at' => now(),
                    ]);
            }
        });

        return redirect()->route('loan-management.payway.checkout', $ref)
            ->with('status', ['success' => 1, 'msg' => 'Payment simulated successfully! Transaction approved.']);
    }

    protected function safeColumns(string $table, array $payload): array
    {
        $columns = Schema::connection($this->conn)->hasTable($table)
            ? Schema::connection($this->conn)->getColumnListing($table)
            : [];
        return array_intersect_key($payload, array_flip($columns));
    }
}
