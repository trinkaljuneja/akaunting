<?php

namespace App\Models\Expense;

use App\Models\Model;
use App\Traits\Currencies;
use App\Traits\DateTime;
use App\Traits\Media;

class BillPayment extends Model
{
    use Currencies, DateTime, Media;

    protected $table = 'bill_payments';

    protected $dates = ['deleted_at', 'paid_at'];

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['company_id', 'bill_id', 'account_id', 'paid_at', 'amount', 'currency_code', 'currency_rate', 'description', 'payment_method', 'reference'];

    public function account()
    {
        return $this->belongsTo('App\Models\Banking\Account');
    }

    public function currency()
    {
        return $this->belongsTo('App\Models\Setting\Currency', 'currency_code', 'code');
    }

    public function bill()
    {
        return $this->belongsTo('App\Models\Expense\Bill');
    }

    public function item()
    {
        return $this->belongsTo('App\Models\Item\Item');
    }

    public function tax()
    {
        return $this->belongsTo('App\Models\Setting\Tax');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('paid_at', 'desc');
    }

    /**
     * Convert amount to double.
     *
     * @param  string  $value
     * @return void
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = (double) $value;
    }

    /**
     * Convert currency rate to double.
     *
     * @param  string  $value
     * @return void
     */
    public function setCurrencyRateAttribute($value)
    {
        $this->attributes['currency_rate'] = (double) $value;
    }

    /**
     * Scope paid invoice.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->sum('amount');
    }

    /**
     * Get the current balance.
     *
     * @return string
     */
    public function getAttachmentAttribute($value)
    {
        if (!empty($value) && !$this->hasMedia('attachment')) {
            return $value;
        } elseif (!$this->hasMedia('attachment')) {
            return false;
        }

        return $this->getMedia('attachment')->last();
    }
}
