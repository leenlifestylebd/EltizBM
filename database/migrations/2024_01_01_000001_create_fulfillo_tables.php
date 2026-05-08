<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('domain')->nullable();
            $table->string('currency', 10)->default('BDT');
            $table->string('currency_symbol', 5)->default('৳');
            $table->boolean('vat_enabled')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->string('vat_label', 20)->default('VAT');
            $table->string('vat_mode', 20)->default('inclusive');
            $table->boolean('tax_enabled')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('tax_label', 20)->default('Tax');
            $table->string('timezone', 50)->default('Asia/Dhaka');
            $table->string('plan', 30)->default('basic');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->enum('role', ['owner','admin','manager','staff','employee'])->default('staff');
            $table->json('permissions')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->string('slug');
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->enum('type', ['text','select','color','size'])->default('select');
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('value');
            $table->string('color_code', 10)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('attribute_id')->references('id')->on('attributes')->cascadeOnDelete();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('low_stock_alert')->default(5);
            $table->enum('type', ['simple','variable'])->default('simple');
            $table->string('unit', 20)->default('pcs');
            $table->decimal('weight', 8, 2)->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('track_stock')->default(true);
            $table->unsignedBigInteger('woo_product_id')->nullable();
            $table->timestamp('woo_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->json('attributes')->nullable();
            $table->unsignedBigInteger('woo_variant_id')->nullable();
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('area')->nullable();
            $table->string('district')->nullable();
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->decimal('total_purchase', 12, 2)->default(0);
            $table->unsignedBigInteger('woo_customer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->string('order_number')->unique();
            $table->enum('source', ['manual','woocommerce','pos','web'])->default('manual');
            $table->enum('status', ['pending','confirmed','processing','shipped','delivered','completed','cancelled','returned'])->default('pending');
            $table->enum('payment_status', ['unpaid','partial','paid','refunded'])->default('unpaid');
            $table->enum('payment_method', ['cash','card','bkash','nagad','rocket','bank','cod','other'])->default('cash');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_area')->nullable();
            $table->string('shipping_district')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('shipping_charge', 12, 2)->default(0);
            $table->decimal('vat', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('woo_order_id')->nullable();
            $table->timestamp('woo_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->integer('quantity');
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('order_id');
            $table->string('courier_name');
            $table->string('tracking_id')->nullable();
            $table->enum('status', ['pending','picked','in_transit','delivered','returned','cancelled'])->default('pending');
            $table->decimal('charge', 12, 2)->default(0);
            $table->decimal('cod_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::create('account_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->enum('type', ['income','expense']);
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->enum('type', ['income','expense']);
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30)->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->decimal('salary', 12, 2)->default(0);
            $table->date('joining_date')->nullable();
            $table->enum('status', ['active','inactive','resigned'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('task_name');
            $table->integer('quantity')->default(0);
            $table->integer('target_quantity')->default(0);
            $table->decimal('quality_rate', 5, 2)->default(100);
            $table->text('notes')->nullable();
            $table->date('log_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('attendance_date');
            $table->enum('status', ['present','absent','late','half_day','holiday'])->default('present');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->unique(['employee_id','attendance_date']);
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->enum('type', ['increase','decrease','damage','return','transfer'])->default('increase');
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->string('po_number')->unique();
            $table->enum('status', ['draft','ordered','received','partial','cancelled'])->default('draft');
            $table->enum('payment_status', ['unpaid','partial','paid'])->default('unpaid');
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('expected_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->integer('quantity');
            $table->integer('received_quantity')->default(0);
            $table->decimal('cost_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->cascadeOnDelete();
        });

        Schema::create('woocommerce_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->unique();
            $table->string('store_url');
            $table->string('consumer_key');
            $table->string('consumer_secret');
            $table->boolean('is_active')->default(false);
            $table->boolean('sync_products')->default(true);
            $table->boolean('sync_orders')->default(true);
            $table->boolean('sync_stock')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('courier_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('provider');
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->unique();
            $table->string('provider')->nullable();
            $table->string('api_key')->nullable();
            $table->string('sender_id')->nullable();
            $table->boolean('order_confirm')->default(false);
            $table->boolean('shipment_update')->default(false);
            $table->boolean('payment_received')->default(false);
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low','medium','high','urgent'])->default('medium');
            $table->enum('status', ['todo','in_progress','done','cancelled'])->default('todo');
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        $tables = ['failed_jobs','jobs','password_reset_tokens','tasks','activity_logs','sms_settings','courier_settings','woocommerce_settings','purchase_order_items','purchase_orders','stock_adjustments','attendance','production_logs','employees','transactions','account_categories','shipments','order_items','orders','vendors','customers','warehouses','product_variants','products','attribute_values','attributes','brands','categories','users','businesses'];
        foreach ($tables as $table) Schema::dropIfExists($table);
    }
};
