<?php

namespace App\Events;

use App\Models\Setting\Currency;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CurrencyRateChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Currency $currency;

    public float $oldRate;

    public float $newRate;

    /**
     * Create a new event instance.
     */
    public function __construct(Currency $currency, float $oldRate, float $newRate)
    {
        $this->currency = $currency;
        $this->oldRate = $oldRate;
        $this->newRate = $newRate;
    }
}
