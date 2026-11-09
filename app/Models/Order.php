<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    /**
     * Orders are only ever built by OrderService from validated, server-computed values (never from raw request
     * input), so the guard list is just the primary key.
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'stock_restored_at' => 'datetime',
        ];
    }

    /** URLs use the human order number (AP...), never the sequential id. */
    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    // ---- relations -------------------------------------------------------
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    // ---- scopes ----------------------------------------------------------
    /**
     * Revenue definition used EVERYWHERE (dashboard, reports, customer lifetime value):
     * an order counts when payment_status is paid (or refunded, to net it out) and the order was not cancelled
     * or returned. Revenue per order = grand_total - refunded_amount. COD orders become "paid" when the
     * administrator marks the cash as collected (automatic on Delivered).
     */
    public function scopeRevenue($query)
    {
        return $query->whereIn('payment_status', ['paid', 'refunded'])
            ->whereNotIn('status', ['cancelled', 'returned']);
    }

    public function scopeStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }

    // ---- helpers ---------------------------------------------------------
    public function getStatusLabelAttribute(): string
    {
        return order_status_label($this->status);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return config("arsh.payment_methods.{$this->payment_method}", $this->payment_method);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return config("arsh.payment_statuses.{$this->payment_status}", $this->payment_status);
    }

    public function getNetRevenueAttribute(): string
    {
        return Money::fromPaise(Money::toPaise($this->grand_total) - Money::toPaise($this->refunded_amount));
    }

    public function getInvoiceNumberAttribute(): string
    {
        return (string) setting('invoice_prefix', 'INV-').$this->order_number;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isCancellable(): bool
    {
        return in_array('cancelled', config("arsh.status_transitions.{$this->status}", []), true);
    }

    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, config('arsh.customer_cancellable_statuses', []), true);
    }

    public function shippingAddressOneLine(): string
    {
        return collect([$this->address_line1, $this->address_line2, $this->city, $this->state, $this->postal_code, $this->country])
            ->filter()->implode(', ');
    }

    /**
     * A wa.me link that opens WhatsApp with the full order pre-filled as a message
     * addressed to the shop's number. The customer taps send and the order lands
     * in the shop owner's WhatsApp chat.
     */
    public function whatsappOrderUrl(): string
    {
        $number = preg_replace('/\D/', '', (string) config('arsh.whatsapp_number', '919724745018'));

        $lines = [];
        $lines[] = '🛍️ *ARSH PERFUME — New Order*';
        $lines[] = 'Order no: *'.$this->order_number.'*';
        $lines[] = '';
        $lines[] = '━━━━━━━━━━━━━━━━';
        $lines[] = '*🧾 Items*';
        foreach ($this->items as $item) {
            $lines[] = '• '.$item->product_name
                .($item->variant_name ? ' ('.$item->variant_name.')' : '')
                .'  ×'.$item->quantity;
            $lines[] = '   '.money($item->line_total);
        }
        $lines[] = '';
        $lines[] = '━━━━━━━━━━━━━━━━';
        $lines[] = '*💰 Summary*';
        $lines[] = 'Subtotal:  '.money($this->subtotal);
        if ((float) $this->discount_total > 0) {
            $lines[] = 'Discount'.($this->coupon_code ? ' ('.$this->coupon_code.')' : '').':  −'.money($this->discount_total);
        }
        $lines[] = 'Shipping:  '.((float) $this->shipping_total > 0 ? money($this->shipping_total) : 'Free');
        $lines[] = '*Total:  '.money($this->grand_total).'*';
        $lines[] = '';
        $lines[] = '━━━━━━━━━━━━━━━━';
        $lines[] = '*📦 Deliver to*';
        $lines[] = $this->name;
        $lines[] = '📞 '.$this->phone;
        $lines[] = '✉️ '.$this->email;
        $lines[] = '🏠 '.$this->shippingAddressOneLine();
        if (filled($this->notes)) {
            $lines[] = '';
            $lines[] = '*📝 Notes*';
            $lines[] = $this->notes;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode(implode("\n", $lines));
    }

    /** Which shipment timeline step is the order on (for tracking progress bars). */
    public function timelineSteps(): array
    {
        return ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
    }
}
