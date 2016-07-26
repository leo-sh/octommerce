<?php namespace Octommerce\Octommerce\Models;

use Backend\Models\ExportModel;

/**
 * OrderExport Model
 */
class OrderExport extends ExportModel
{
    protected $fillable = ['start_date', 'end_date', 'status'];

    public function exportData($columns, $sessionKey = null)
    {
        $query = Order::query();

        if ($this->start_date) {
            $query->whereDate('created_at', '>=', $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate('created_at', '<=', $this->end_date);
        }

        if($this->status) {
            $query->whereStatusCode($this->status);
        }


        $orders = $query->get();

        $orders->each(function($order) use ($columns) {
            $order->addVisible($columns);

            $city = $order->city;
            if($city) {
                $order->usercity = $city->name;
            }

            $state = $order->state;
            if($state) {
                $order->userstate = $state->name;
            }

            $order->user_shipping_city = $order->shipping_city_name ? $order->shipping_city_name : 'sdfas';

            $order->user_shipping_state = $order->shipping_state_name ? $order->shipping_state_name : 'fasf';

            $invoices = $order->invoices;

            $detailOrder = "";
            foreach ($order->products as $key => $product) {
                $detailOrder .= $product->sku;
                if($key+1 == $order->products()->count() && $order->products()->count() > 0) {
                    $detailOrder .= ",";
                }
            }
            $order->sku = $detailOrder;

            $order->total_order = $order->products()->count();

            foreach($invoices as $invoice) {
               $order->payment_method = $invoice->payment_method->name;
               $order->unique_code = $invoice->unique_number > 0 ? $invoice->unique_number : null;
               $order->due_at = $invoice->due_at ? $invoice->due_at : null;
            }

            $status = $order->status;
            if($status) {
                $order->status = $status->name;
            }
        });

        return $orders;
    }
}