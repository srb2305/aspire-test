<?php
namespace App\Traits;
use App\Models\CustomerLoan;
use App\Models\ScheduledRepayment;
use DB;

trait CommonTrait {

	/**
	 * Return proper formate of amount like
	 *
	 * @param $amount
	 *
	 * @return float
	 */
	public function traitAmount($amount) {
		return sprintf('%.2f', $amount);
	}

	/**
     * Update loan pending amount to customer_loans table
     */
    public function traitUpdatePendingAmount($loanId){
        $paidAmt = ScheduledRepayment::where('loan_id',$loanId)
	            ->where('state','PAID')
	            ->sum('paid_amount');

        $loan = CustomerLoan::where('loan_id',$loanId)->first();
        $pendingAmt = $loan['loan_amout'] - $paidAmt;
        $loan['loan_pending_amout'] = $pendingAmt;
        $loan->save();

        return true;
    }

    /**
     * ### If all the scheduled repayments connected to a loan are PAID automatically also the loan become PAID
     */
    public function traitLoanBecomePaid($loanId){
        $check = ScheduledRepayment::where('loan_id',$loanId)
	            ->where('state','PENDING')
	            ->first();
	    if(empty($check)){
	    	$loan = CustomerLoan::where('loan_id',$loanId)->first();
	        $loan['state'] = 'PAID';
	        $loan->save();
        }
        return true;
    }

    /**
     * Generate a new loan id.
     */
    public function traitGetNextLoanID() 
    {
        $statement = DB::select("show table status like 'customer_loans'");
        return 10000 + $statement[0]->Auto_increment;
    }
}