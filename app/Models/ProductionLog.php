<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductionLog extends Model {
    protected $fillable = ['business_id','employee_id','product_id','task_name','quantity','target_quantity','quality_rate','notes','log_date','start_time','end_time'];
    protected $casts = ['quality_rate'=>'decimal:2','log_date'=>'date'];
    public function business() { return $this->belongsTo(Business::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
