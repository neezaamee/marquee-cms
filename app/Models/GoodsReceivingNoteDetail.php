<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceivingNoteDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receiving_note_id',
        'item_id',
        'ordered_qty',
        'received_qty',
    ];

    protected $casts = [
        'ordered_qty' => 'float',
        'received_qty' => 'float',
    ];

    /**
     * Get the parent goods receiving note.
     */
    public function goodsReceivingNote()
    {
        return $this->belongsTo(GoodsReceivingNote::class, 'goods_receiving_note_id');
    }

    /**
     * Get the item catalog.
     */
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
