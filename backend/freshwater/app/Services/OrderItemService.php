<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderItemService
{
    public function __construct(protected OrderService $orderService) {}

    public function create(array $data): OrderItem
    {
       return DB::transaction(function () use ($data) 
       {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            $quantity = $data['quantity'] ?? null;

            if($quantity!==null && $product->quantity < $quantity) {
                throw new \Exception('Продуктът няма наличност.');
            }

            $orderItem = new OrderItem();
            $orderItem->order_id = $data['order_id'];
            $orderItem->product_id = $product->id;
            $orderItem->quantity = $quantity;

            //snapshot
            $orderItem->product_name = $product->name;
            $orderItem->price = $product->price;
            $orderItem->total = $quantity !==null
                ? $product->price * $quantity
                : $product->price;

            $orderItem->save();

            if($quantity!==null){
                $product->quantity -= $quantity;
                $product->save();
            }

            $this->orderService->recalculateTotal($orderItem->order);

            return $orderItem;
       });
    }

    public function update(OrderItem $orderItem, array $data): OrderItem
    {
        return DB::transaction(function () use ($orderItem, $data) 
        {
            $product = Product::lockForUpdate()->findOrFail($orderItem->product_id);

            $oldQuantity = $orderItem->quantity;
            $newQuantity = $data['quantity'] ?? null;

            //null -> number (Добавяме количество)
            if($oldQuantity ===null && $newQuantity !== null){
                if($product->quantity < $newQuantity){
                    throw new \Exception('Продуктът няма наличност.');
                }
                $product->quantity -= $newQuantity;
            }

            //number -> null (Премахваме количество)
            if($oldQuantity !==null && $newQuantity === null){
                $product->quantity += $oldQuantity;
            }

            //number -> number (Променяме количество)
            if($oldQuantity !==null && $newQuantity !== null){
                //увеличаваме количество
                $difference = $newQuantity - $oldQuantity;

                if($difference > 0 && $product->quantity < $difference){
                    throw new \Exception('Продуктът няма наличност.');
                }
                    $product->quantity -= $difference;
            }

            //Update order item
            $orderItem->quantity = $newQuantity;
            $orderItem->total = $newQuantity !==null
                ? $orderItem->price * $newQuantity
                : $orderItem->price;

            $orderItem->save();
            $product->save();

            $this->orderService->recalculateTotal($orderItem->order);
            return $orderItem;
        });
    }

    public function delete(OrderItem $orderItem): void
    {
        DB::transaction(function () use ($orderItem) 
        {
            if($orderItem->quantity !== null){
                $product = Product::lockForUpdate()->findOrFail($orderItem->product_id);
                if($product){
                    $product->quantity += $orderItem->quantity;
                    $product->save();
                }
            }

            $order = $orderItem->order;
            $orderItem->delete();

            if($order){
                $this->orderService->recalculateTotal($order);
            }
        });
    }
}