<?php

namespace App\Http\Controllers;

use App\Models\CustomerLoan;
use App\Models\ScheduledRepayment;
use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\Traits\CommonTrait;
use Auth;
use DB;

class LoanController extends Controller
{   
    use CommonTrait;

    /**
     * Request For new loan (api/loan_request)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function loanRequest(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'amount' => 'required|integer',
            'term' => 'required|integer'
        ]);

        if($validator->fails()){
            return response(['error' => $validator->errors(), 
            'Validation Error']);
        }

        $insert['loan_id'] = $this->traitGetNextLoanID();
        $insert['user_id'] = Auth::user()->id;
        $insert['loan_amout'] = $data['amount'];
        $insert['loan_pending_amout'] = $data['amount'];
        $insert['term'] = $data['term'];
        $insert['state'] = 'PENDING';
        $insert['created_at'] = Carbon::now();
      
        $inserteddata = CustomerLoan::create($insert);
        $data['loan_id'] = $inserteddata->loan_id;

        $this->_scheduleRepayments($data);

        return response([ 'data' => new 
        LoanResource($inserteddata),'message' => 'Success'], 200); 
    }

    /**
     * Loan approve by admin(api/loan_approve)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function loanApprove(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'loan_id' => 'required|integer'
        ]);

        if($validator->fails()){
            return response(['error' => $validator->errors(), 
            'Validation Error']);
        }

        ##### Check Loggedin role is Admin or not #####
        if(Auth::user()->role != 'ADMIN'){
            return response(['error' => 'You dont have permission of this request','Error']);
        }

        ##### Check loan id valid or not #####
        $check = CustomerLoan::where('loan_id',$data['loan_id'])->first();

        if(empty($check)){
            return response(['error' => 'Invalid loan id','Error']);
        }
        
        ### Admin change the pending loans to state APPROVED
        $check->state = 'APPROVED';
        $check->save();

        return response(['message' => 'Success'], 200); 
    }

    /**
     * Loan details by customer(api/loan_details)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function loanDetails(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'loan_id' => 'required|integer'
        ]);

        if($validator->fails()){
            return response(['error' => $validator->errors(), 
            'Validation Error']);
        }

        ##### Add a policy check to make sure that the customers can view them own loan only.  #####

        $data = CustomerLoan::select('loan_id','loan_amout','loan_pending_amout','term','state')
            ->where('loan_id',$data['loan_id'])
            ->where('user_id',Auth::user()->id)
            ->first();

        if(empty($data)){
            return response(['error' => 'Invalid loan id OR you dont have permission to access this loan details','Error']);
        }

        $data['loan_amout'] = $this->traitAmount($data['loan_amout']);
        $data['loan_pending_amout'] = $this->traitAmount($data['loan_pending_amout']);

        #### Get all paid/pending data
        $repaymentData = ScheduledRepayment::select('due_date','due_amount','paid_amount','state')->where('loan_id',$data['loan_id'])->get();

        $repaymentArray = [];
        foreach($repaymentData as $key=>$val){
            $date = Carbon::createFromFormat('Y-m-d', $val->due_date)->format('d M Y');
            $ary = [
                'date' => $date,
                'due_amount' => $this->traitAmount($val->due_amount), 
                'paid_amount' => !empty($val->paid_amount) ? $this->traitAmount($val->paid_amount) : '', 
                'state' => $val->state, 
            ];
            array_push($repaymentArray,$ary);
        }

        $data['scheduled_repayments'] = $repaymentArray;
        
        return response([ 'data' => new 
        LoanResource($data), 'message' => 'Success'], 200);
    }

    /**
     * Loan Repayment by customer(api/loan_repayment)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function loanRepayment(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'loan_id' => 'required|integer',
            'amount' => 'required|numeric',
        ]);

        if($validator->fails()){
            return response(['error' => $validator->errors(), 
            'Validation Error']);
        }

        ##### Check loan_id valid or not #####

        $check = CustomerLoan::where('loan_id',$data['loan_id'])->first();

        if(empty($check)){
            return response(['error' => 'Invalid loan id','Error']);
        }

        $amount = $data['amount'];

        $repaymentData = ScheduledRepayment::where('loan_id',$data['loan_id'])
            ->where('state','PENDING')
            ->orderBy('id','ASC')
            ->first();
        
        #### Check PENDING payment exist or not 
        if(empty($repaymentData)){
            return response(['error' => 'No any pending repayment found','Error']);
        }

        ### Check amount is greater or equal to the scheduled repayment
        if($repaymentData['due_amount'] > $amount){
            return response(['error' => 'Amount should be greater or equal to the scheduled amount='.$repaymentData['due_amount'],'Error']);
        }

        ### The scheduled repayment change the status to PAID
        $repaymentData['paid_amount'] = $amount;
        $repaymentData['state'] = 'PAID';
        $repaymentData['payment_datetime'] = Carbon::now();
        $repaymentData->save();

        ### update pending/remaining amount to customer_loans table
        $this->traitUpdatePendingAmount($data['loan_id']);
        
        ### If all the scheduled repayments connected to a loan are PAID automatically also the loan become PAID
         $this->traitLoanBecomePaid($data['loan_id']);

        return response(['message' => 'Success'], 200);
    }

    public function _scheduleRepayments($data){
       
        $emi = $data['amount'] / $data['term'];
        $emi = $this->traitAmount($emi);
        $date = date('Y-m-d');
        $repaymentAry = [];

        for ($i=0; $i < $data['term']; $i++) { 
            $date = Carbon::createFromFormat('Y-m-d', $date)->addDays(7)->toDateString();
            
            $ary = [
                'loan_id' => $data['loan_id'],
                'due_date' => $date,
                'due_amount' => $emi,
                'paid_amount' => null,
                'state' => 'PENDING',
                'payment_datetime' => null,
                'created_at' =>Carbon::now()
            ];
            array_push($repaymentAry,$ary);
        }

        ScheduledRepayment::insert($repaymentAry);
        return true;
    }
}