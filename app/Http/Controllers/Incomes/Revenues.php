<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Income\Revenue as Request;
use App\Models\Banking\Account;
use App\Models\Income\Customer;
use App\Models\Income\Revenue;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Traits\Currencies;
use App\Traits\DateTime;
use App\Traits\Uploads;
use App\Utilities\ImportFile;
use App\Utilities\Modules;

class Revenues extends Controller
{
    use DateTime, Currencies, Uploads;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $revenues = Revenue::with(['account', 'category', 'customer'])->collect(['paid_at'=> 'desc']);

        $customers = collect(Customer::enabled()->pluck('name', 'id'))
            ->prepend(trans('general.all_type', ['type' => trans_choice('general.customers', 2)]), '');

        $categories = collect(Category::enabled()->type('income')->pluck('name', 'id'))
            ->prepend(trans('general.all_type', ['type' => trans_choice('general.categories', 2)]), '');

        $accounts = collect(Account::enabled()->pluck('name', 'id'))
            ->prepend(trans('general.all_type', ['type' => trans_choice('general.accounts', 2)]), '');

        $transfer_cat_id = Category::transfer();

        return view('incomes.revenues.index', compact('revenues', 'customers', 'categories', 'accounts', 'transfer_cat_id'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $accounts = Account::enabled()->pluck('name', 'id');

        $currencies = Currency::enabled()->pluck('name', 'code')->toArray();

        $account_currency_code = Account::where('id', setting('general.default_account'))->pluck('currency_code')->first();

        $customers = Customer::enabled()->pluck('name', 'id');

        $categories = Category::enabled()->type('income')->pluck('name', 'id');

        $payment_methods = Modules::getPaymentMethods();

        return view('incomes.revenues.create', compact('accounts', 'currencies', 'account_currency_code', 'customers', 'categories', 'payment_methods'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     *
     * @return Response
     */
    public function store(Request $request)
    {
        // Get currency object
        $currency = Currency::where('code', $request['currency_code'])->first();

        $request['currency_code'] = $currency->code;
        $request['currency_rate'] = $currency->rate;

        $revenue = Revenue::create($request->input());

        // Upload attachment
        if ($request->file('attachment')) {
            $media = $this->getMedia($request->file('attachment'), 'revenues');

            $revenue->attachMedia($media, 'attachment');
        }

        $message = trans('messages.success.added', ['type' => trans_choice('general.revenues', 1)]);

        flash($message)->success();

        return redirect('incomes/revenues');
    }

    /**
     * Duplicate the specified resource.
     *
     * @param  Revenue  $revenue
     *
     * @return Response
     */
    public function duplicate(Revenue $revenue)
    {
        $clone = $revenue->duplicate();

        $message = trans('messages.success.duplicated', ['type' => trans_choice('general.revenues', 1)]);

        flash($message)->success();

        return redirect('incomes/revenues/' . $clone->id . '/edit');
    }

    /**
     * Import the specified resource.
     *
     * @param  ImportFile  $import
     *
     * @return Response
     */
    public function import(ImportFile $import)
    {
        $rows = $import->all();

        foreach ($rows as $row) {
            $data = $row->toArray();
            $data['company_id'] = session('company_id');

            Revenue::create($data);
        }

        $message = trans('messages.success.imported', ['type' => trans_choice('general.revenues', 2)]);

        flash($message)->success();

        return redirect('incomes/revenues');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Revenue  $revenue
     *
     * @return Response
     */
    public function edit(Revenue $revenue)
    {
        $accounts = Account::enabled()->pluck('name', 'id');

        $currencies = Currency::enabled()->pluck('name', 'code')->toArray();

        $account_currency_code = Account::where('id', $revenue->account_id)->pluck('currency_code')->first();

        $customers = Customer::enabled()->pluck('name', 'id');

        $categories = Category::enabled()->type('income')->pluck('name', 'id');

        $payment_methods = Modules::getPaymentMethods();

        return view('incomes.revenues.edit', compact('revenue', 'accounts', 'currencies', 'account_currency_code', 'customers', 'categories', 'payment_methods'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Revenue  $revenue
     * @param  Request  $request
     *
     * @return Response
     */
    public function update(Revenue $revenue, Request $request)
    {
        // Get currency
        $currency = Currency::where('code', $request['currency_code'])->first();

        $request['currency_code'] = $currency->code;
        $request['currency_rate'] = $currency->rate;

        $revenue->update($request->input());

        // Upload attachment
        if ($request->file('attachment')) {
            $media = $this->getMedia($request->file('attachment'), 'revenues');

            $revenue->attachMedia($media, 'attachment');
        }

        $message = trans('messages.success.updated', ['type' => trans_choice('general.revenues', 1)]);

        flash($message)->success();

        return redirect('incomes/revenues');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Revenue  $revenue
     *
     * @return Response
     */
    public function destroy(Revenue $revenue)
    {
        // Can't delete transfer revenue
        if ($revenue->category->id == Category::transfer()) {
            return redirect('incomes/revenues');
        }

        $revenue->delete();

        $message = trans('messages.success.deleted', ['type' => trans_choice('general.revenues', 1)]);

        flash($message)->success();

        return redirect('incomes/revenues');
    }
}
