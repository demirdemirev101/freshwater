<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * This service handles the creation, updating, and deletion of order items while ensuring that product stock levels are accurately maintained.
 */
class OrderItemService
{
    public function __construct(protected OrderService $orderService) {}

    /**
     * Create a new order item and adjust product stock accordingly. This method performs the following steps:
     *  1. It starts a database transaction to ensure atomicity of the operation.
     *  2. It locks the product record for update to prevent concurrent modifications to the product's stock.
     *  3. It checks if the requested quantity is available in stock. If not, it throws an exception indicating insufficient stock.
     *  4. It creates a new order item with the provided data and calculates the total price based on the product's price and the quantity.
     *  5. It updates the product's stock by decrementing the quantity based on the order item quantity.
     *  6. It recalculates the total for the associated order to reflect the new item and saves the changes to the database.
     *  7. Finally, it returns the created order item.
     */
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
    /**
     * Update an existing order item and adjust product stock accordingly. This method performs the following steps:
     *  1. It starts a database transaction to ensure atomicity of the operation.
     *  2. It locks the product record for update to prevent concurrent modifications to the product's stock.
     *  3. It checks the old and new quantities to determine how to adjust the product's stock. If the new quantity is greater than the old quantity,
     *    it checks if the additional quantity is available in stock. If not, it throws an exception indicating insufficient stock. 
     *    If the new quantity is less than the old quantity, it calculates the difference and adjusts the stock accordingly.
     *  4. It updates the order item with the new quantity and recalculates the total price based on the product's price and the new quantity.
     *  5. It saves the changes to the order item and the product, and then recalculates the total for the associated order to reflect the updated item.
     *  6. Finally, it returns the updated order item.
     *  
    */
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
    /**
     * Delete an order item and adjust product stock accordingly. This method performs the following steps:
     *  1. It starts a database transaction to ensure atomicity of the operation.
     *  2. It checks if the order item has a quantity specified. If it does,
     *   it locks the associated product record for update and increments the product's stock by the quantity of the order item being deleted.
     *  3. It deletes the order item from the database.
     *  4. It recalculates the total for the associated order to reflect the removed item and saves the changes to the database.
     *  5. Finally, it completes the transaction, ensuring that all changes are applied atomically to maintain data integrity.
     */
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