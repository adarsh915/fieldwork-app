<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ReportsCache extends Model {
    protected $table = 'reports_cache';
    protected $fillable = ['report_date', 'data'];
    protected $casts = ['report_date' => 'date', 'data' => 'array'];
}