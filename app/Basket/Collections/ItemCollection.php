<?php

namespace App\Basket\Collections;

use App\Events\ItemAdded;
use App\Basket\Models\Item;
use App\Events\ItemRemoved;
use App\Events\ItemUpdated;
use App\Events\BasketException;
use App\Basket\Exceptions\Exception;
use App\Basket\Traits\HasConstraints;
use App\Basket\Collections\Collection;
use App\Basket\Models\TransactionHeader;
use App\Basket\Constraints\ItemConstraint;

class ItemCollection extends Collection
{
    use HasConstraints;

    /**
     * Basket instance.
     *
     * @var App\Basket\Basket
     */
    protected $basket;

    /**
     * Quantity value needed since you can't overload method arguments.
     *
     * @var integer
     */
    protected $addCount = null;

    /**
     * Constructor method.
     *
     * @return any
     */
    public function __construct($items, $basket = null)
    {
        $this->basket = $basket;

        // Register the constraint class
        $this->constraint(new ItemConstraint);

        foreach ($items as $item) {
            $this->push($item);
        }
    }

    /**
     * Gets the count of all items.
     * Factors in each item's quantity.
     *
     * @return integer
     */
    public function count()
    {
        $count = 0;

        $this->each(function($item) use(&$count) {
            $count += $item->qty;
        });

        return $count;
    }

    /**
     * Gets the balance of all items.
     *
     * @return App\Basket\Support\Number
     */
    public function balance()
    {
        $total = 0;

        $this->each(function($item) use(&$total) {
            $total += $item->qty * $item->model->gross;
        });

        return number($total);
    }

    /**
     * Checks if the collection has the given item.
     *
     * @return boolean
     */
    public function has($item)
    {
        return $this->contains(function($i) use($item) {
            return $i->isSameAs($item);
        });
    }

    /**
     * Checks if the collection has one of the given items.
     *
     * @return boolean
     */
    public function hasOneOf($items)
    {
        return $this->contains(function($i) use($items) {
            return $items->contains(function($ii) use($i) {
                return $ii->isSameAs($i);
            });
        });
    }

    /**
     * Resolves the item model while keeping dynamic properties.
     *
     * @return App\Basket\Models\Item
     */
    public function resolve($item, array $props = [])
    {
        $model = ($item instanceof Item) ? $item : Item::findOrFail($item->id);

        foreach ($props as $key => $value) {
            $model->$key = $value;
        }

        return $model;
    }

    /**
     * Adds an item to the collection for the given number of times.
     *
     * @return self
     */
    public function addMany($item, int $count = 1)
    {
        $this->addCount = $count;

        $this->add($item);

        return $this;
    }

    /**
     * Adds an item to the collection.
     *
     * @return self
     */
    public function add($item)
    {
        $addCount = $this->addCount;

        return $this->basket->update(function($basket) use($item, $addCount) {
            $item = $this->resolve($item);

            // Validate the item, if invalid, will exit
            if (! $this->constraint(compact('basket', 'item'))->passes('adding')) {
                return $basket->exception($this->constraint()->reason());
            }

            if ($addCount) {
                if (! $this->has($item)) {
                    $this->push($item);
                    $addCount--;
                }

                $this->update($item, function(&$item) use($addCount) {
                    $item->qty += $addCount;
                });
            } else {
                if ($this->has($item)) {
                    $this->update($item, function(&$item) {
                        $item->qty++;
                    });
                } else {
                    $this->push($item);
                }
            }

            // Return the updated basket, with the item added event
            return $basket->withEvent(ItemAdded::class, $item);
        });
    }

    /**
     * Removes the given item from the collection.
     *
     * @return self
     */
    public function remove(Item $item, int $qty = -1)
    {
        $this->items = $this->map(function($i) use($item, $qty) {
            if ($i->isSameAs($item)) {
                if ($qty === -1 || $i->qty <= 1) {
                    return null;
                }

                $i->qty -= $qty;
                return $i;
            }

            return $i;
        })->reject(function($i) {
            return is_null($i);
        })->all();

        event(new ItemRemoved($item));
    }

    /**
     * Updates the given item via the closure.
     *
     * @return self
     */
    public function update(Item $item, callable $closure)
    {
        $this->each(function(&$i) use($item, $closure) {
            if ($i->isSameAs($item)) {
                $closure($i);
            }
        });

        event(new ItemUpdated($item));

        return $this;
    }

    /**
     * Gets the items grouped by the given column.
     *
     * @return self
     */
    public function grouped(string $column = 'model_type')
    {
        return $this->groupBy($column);
    }

    /**
     * Commits the items to transaction header items.
     *
     * @return self
     */
    public function commit(TransactionHeader $header)
    {
        $this->each(function($item) use($header) {
            $header->items()->create([
                'model_id' => $item->model_id,
                'model_type' => $item->model_type,
                'qty' => $item->qty,
                'net' => $item->model->net,
                'gross' => $item->model->gross,
                'vat' => $item->model->vat
            ]);
        });

        return $this;
    }
}
