<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Investor;
use App\Models\InvestorProfitTransaction;
use App\Models\InvestorPurchaseFunding;
use App\Models\InvestorPayment;
use App\Models\InvestorPaymentAllocation;
use App\Models\InvestorLedgerEntry;
use App\Models\SaleInvestorAllocation;
use App\Models\InvestorSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvestorService
{
    /**
     * Check if the investor module is enabled.
     */
    public function isEnabled(): bool
    {
        return InvestorSetting::get('module_enabled', '0') === '1';
    }

    /**
     * Calculate and allocate profit for a sale.
     */
    public function allocateSaleProfit(Sale $sale, array $investorAllocations = [])
    {
        if (!$this->isEnabled()) {
            return;
        }

        DB::transaction(function () use ($sale, $investorAllocations) {
            // First, reverse any existing allocations for this sale to handle edits cleanly
            $this->reverseSaleProfit($sale, 'sale_edit');

            if (empty($investorAllocations)) {
                return;
            }

            // Calculate cost of goods and total eligible profit for the sale
            $costOfGoods = $sale->items->sum('subtotal_cost') ?? $sale->items->sum(function ($item) {
                return $item->cost_price * $item->quantity;
            });
            
            $netSalesAmount = $sale->subtotal_amount - $sale->discount_amount;
            $grossProfit = $netSalesAmount - $costOfGoods;
            
            // If settings use 'net' profit, deduct expenses if any are linked to sale. 
            // For now, assuming gross profit based on requirements.
            $calculationMethod = InvestorSetting::get('profit_calculation_method', 'gross');
            $deductedExpenses = 0;
            $eligibleProfit = $calculationMethod === 'net' ? ($grossProfit - $deductedExpenses) : $grossProfit;

            foreach ($investorAllocations as $allocation) {
                $investorId = $allocation['investor_id'];
                $percentage = $allocation['percentage'];
                $investor = Investor::find($investorId);

                if (!$investor || !$investor->is_active) {
                    continue;
                }

                $investorProfitAmount = ($eligibleProfit * $percentage) / 100;

                // Create allocation record
                SaleInvestorAllocation::create([
                    'sale_id' => $sale->id,
                    'investor_id' => $investor->id,
                    'percentage' => $percentage,
                    'profit_amount' => $investorProfitAmount,
                    'status' => 'allocated',
                ]);

                // Create profit transaction
                $transaction = InvestorProfitTransaction::create([
                    'sale_id' => $sale->id,
                    'investor_id' => $investor->id,
                    'date' => $sale->date,
                    'sales_subtotal' => $sale->subtotal_amount,
                    'discount' => $sale->discount_amount,
                    'tax' => $sale->tax_amount,
                    'net_sales_amount' => $netSalesAmount,
                    'cost_of_goods' => $costOfGoods,
                    'gross_profit' => $grossProfit,
                    'deducted_expenses' => $deductedExpenses,
                    'eligible_profit' => $eligibleProfit,
                    'investor_percentage' => $percentage,
                    'investor_profit_amount' => $investorProfitAmount,
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'calculation_method' => $calculationMethod,
                    'created_by' => auth()->id(),
                ]);

                // Update ledger
                $this->addLedgerEntry(
                    $investor,
                    $sale->date,
                    $sale->invoice_no,
                    'sale_profit',
                    $transaction,
                    "Profit allocated from Sale #{$sale->invoice_no}",
                    $investorProfitAmount,
                    0,
                    0,
                    0
                );
            }
        });
    }

    /**
     * Reverse sale profit allocations (e.g. on return or cancellation).
     */
    public function reverseSaleProfit(Sale $sale, string $reason = 'sale_return')
    {
        DB::transaction(function () use ($sale, $reason) {
            $transactions = InvestorProfitTransaction::where('sale_id', $sale->id)
                ->where('status', '!=', 'reversed')
                ->get();

            foreach ($transactions as $transaction) {
                $investor = $transaction->investor;

                // Update ledger to reverse
                $this->addLedgerEntry(
                    $investor,
                    now(),
                    $sale->invoice_no,
                    'reversal',
                    $transaction,
                    "Reversal: {$reason} for Sale #{$sale->invoice_no}",
                    0,
                    $transaction->investor_profit_amount,
                    0,
                    0
                );

                $transaction->update(['status' => 'reversed']);
            }
            
            SaleInvestorAllocation::where('sale_id', $sale->id)->update(['status' => 'reversed']);
        });
    }

    /**
     * Fund a purchase.
     */
    public function fundPurchase(Purchase $purchase, array $fundings)
    {
        if (!$this->isEnabled()) {
            return;
        }

        DB::transaction(function () use ($purchase, $fundings) {
            // Reverse existing fundings for edits
            $this->reversePurchaseFunding($purchase, 'purchase_edit');
            
            foreach ($fundings as $funding) {
                $investorId = $funding['investor_id'];
                $amount = $funding['amount'];
                $investor = Investor::find($investorId);

                if (!$investor || !$investor->is_active || $amount <= 0) {
                    continue;
                }

                $fundingRecord = InvestorPurchaseFunding::create([
                    'purchase_id' => $purchase->id,
                    'investor_id' => $investor->id,
                    'funded_amount' => $amount,
                    'repaid_amount' => 0,
                    'payment_method' => $funding['payment_method'] ?? null,
                    'reference_no' => $funding['reference_no'] ?? null,
                    'bank_account' => $funding['bank_account'] ?? null,
                    'notes' => $funding['notes'] ?? null,
                    'status' => 'unpaid',
                ]);

                // Update ledger
                $this->addLedgerEntry(
                    $investor,
                    $purchase->date,
                    $purchase->invoice_no,
                    'purchase_funding',
                    $fundingRecord,
                    "Funded Purchase #{$purchase->invoice_no}",
                    0,
                    0,
                    $amount,
                    0
                );
            }
        });
    }
    
    /**
     * Reverse purchase funding (e.g. on return or edit).
     */
    public function reversePurchaseFunding(Purchase $purchase, string $reason = 'purchase_return')
    {
        DB::transaction(function () use ($purchase, $reason) {
            $fundings = InvestorPurchaseFunding::where('purchase_id', $purchase->id)
                ->where('status', '!=', 'reversed')
                ->get();

            foreach ($fundings as $funding) {
                $investor = $funding->investor;

                // Update ledger to reverse
                $this->addLedgerEntry(
                    $investor,
                    now(),
                    $purchase->invoice_no,
                    'reversal',
                    $funding,
                    "Reversal: {$reason} for Purchase #{$purchase->invoice_no}",
                    0,
                    0,
                    0,
                    $funding->funded_amount
                );

                $funding->update(['status' => 'reversed']);
            }
        });
    }

    /**
     * Process a payment to an investor.
     */
    public function processPayment(Investor $investor, array $data)
    {
        return DB::transaction(function () use ($investor, $data) {
            $profitPaymentAmount = $data['profit_payment_amount'] ?? 0;
            $purchaseRepaymentAmount = $data['purchase_repayment_amount'] ?? 0;
            $totalPayment = $profitPaymentAmount + $purchaseRepaymentAmount;

            if ($totalPayment <= 0) {
                throw new \Exception("Payment amount must be greater than zero.");
            }

            $paymentNo = 'IP-' . strtoupper(Str::random(6));

            $payment = InvestorPayment::create([
                'investor_id' => $investor->id,
                'payment_no' => $paymentNo,
                'date' => $data['date'] ?? now(),
                'payment_type' => $data['payment_type'] ?? 'combined',
                'profit_payment_amount' => $profitPaymentAmount,
                'purchase_repayment_amount' => $purchaseRepaymentAmount,
                'total_payment' => $totalPayment,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Allocate profit payments
            if ($profitPaymentAmount > 0) {
                $this->allocatePaymentToTransactions($payment, 'profit');
                
                $this->addLedgerEntry(
                    $investor,
                    $payment->date,
                    $payment->payment_no,
                    'profit_payment',
                    $payment,
                    "Profit Payment #{$payment->payment_no}",
                    0,
                    $profitPaymentAmount,
                    0,
                    0
                );
            }

            // Allocate purchase repayments
            if ($purchaseRepaymentAmount > 0) {
                $this->allocatePaymentToTransactions($payment, 'purchase');
                
                $this->addLedgerEntry(
                    $investor,
                    $payment->date,
                    $payment->payment_no,
                    'purchase_repayment',
                    $payment,
                    "Purchase Repayment #{$payment->payment_no}",
                    0,
                    0,
                    0,
                    $purchaseRepaymentAmount
                );
            }

            return $payment;
        });
    }

    /**
     * Allocate payment automatically to oldest transactions.
     */
    protected function allocatePaymentToTransactions(InvestorPayment $payment, string $type)
    {
        $amountRemaining = $type === 'profit' ? $payment->profit_payment_amount : $payment->purchase_repayment_amount;

        if ($type === 'profit') {
            $transactions = InvestorProfitTransaction::where('investor_id', $payment->investor_id)
                ->where('status', '!=', 'reversed')
                ->where('status', '!=', 'paid')
                ->orderBy('date', 'asc')
                ->get();
                
            foreach ($transactions as $transaction) {
                if ($amountRemaining <= 0) break;

                $due = $transaction->investor_profit_amount - $transaction->paid_amount;
                $pay = min($due, $amountRemaining);

                $transaction->paid_amount += $pay;
                $transaction->status = $transaction->paid_amount >= $transaction->investor_profit_amount ? 'paid' : 'partial';
                $transaction->save();

                InvestorPaymentAllocation::create([
                    'investor_payment_id' => $payment->id,
                    'allocatable_type' => get_class($transaction),
                    'allocatable_id' => $transaction->id,
                    'amount' => $pay,
                ]);

                $amountRemaining -= $pay;
            }
        } else {
            $fundings = InvestorPurchaseFunding::where('investor_id', $payment->investor_id)
                ->where('status', '!=', 'reversed')
                ->where('status', '!=', 'paid')
                ->orderBy('created_at', 'asc')
                ->get();
                
            foreach ($fundings as $funding) {
                if ($amountRemaining <= 0) break;

                $due = $funding->funded_amount - $funding->repaid_amount;
                $pay = min($due, $amountRemaining);

                $funding->repaid_amount += $pay;
                $funding->status = $funding->repaid_amount >= $funding->funded_amount ? 'paid' : 'partial';
                $funding->save();

                InvestorPaymentAllocation::create([
                    'investor_payment_id' => $payment->id,
                    'allocatable_type' => get_class($funding),
                    'allocatable_id' => $funding->id,
                    'amount' => $pay,
                ]);

                $amountRemaining -= $pay;
            }
        }
    }

    /**
     * Add a ledger entry and update running balances.
     */
    protected function addLedgerEntry(
        Investor $investor,
        $date,
        $transactionNo,
        $transactionType,
        $source,
        $description,
        $profitDebit,
        $profitCredit,
        $purchaseDebit,
        $purchaseCredit
    ) {
        $lastEntry = InvestorLedgerEntry::where('investor_id', $investor->id)
            ->orderBy('id', 'desc')
            ->first();

        $profitBalance = ($lastEntry ? $lastEntry->profit_balance : $investor->opening_profit_balance) + $profitDebit - $profitCredit;
        $purchaseBalance = ($lastEntry ? $lastEntry->purchase_balance : $investor->opening_purchase_balance) + $purchaseDebit - $purchaseCredit;
        $totalPayable = $profitBalance + $purchaseBalance;

        InvestorLedgerEntry::create([
            'investor_id' => $investor->id,
            'date' => $date,
            'transaction_no' => $transactionNo,
            'transaction_type' => $transactionType,
            'source_type' => $source ? get_class($source) : null,
            'source_id' => $source ? $source->id : null,
            'description' => $description,
            'profit_debit' => $profitDebit,
            'profit_credit' => $profitCredit,
            'profit_balance' => $profitBalance,
            'purchase_debit' => $purchaseDebit,
            'purchase_credit' => $purchaseCredit,
            'purchase_balance' => $purchaseBalance,
            'total_payable_balance' => $totalPayable,
            'created_by' => auth()->id() ?? null,
        ]);
    }
}
