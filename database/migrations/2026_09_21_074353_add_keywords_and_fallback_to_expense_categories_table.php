<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddKeywordsAndFallbackToExpenseCategoriesTable extends Migration
{
    // Starter keywords for categories that already exist (matched by current name).
    // Categories the admin creates later start empty and use their name as an implicit keyword.
    private array $defaults = [
        'Food & Dining' => 'chicken,chickenjoy,jollibee,mcdo,burger,fries,rice,ulam,lunch,dinner,breakfast,merienda,snack,milk tea,coffee,pizza,pasta,noodles,siomai,fishball,kwek-kwek,isaw,sisig,adobo,tapsilog,silog,shawarma,canteen,carinderia,softdrink,coke,ice cream,bread,meal,food,drink,water bottle',
        'Transportation' => 'jeep,jeepney,tricycle,trike,habal,angkas,grab,fare,bus,van,taxi,mrt,lrt,ferry,pedicab,gas,gasoline,parking,toll,commute',
        'Academics & Supplies' => 'book,notebook,pen,ballpen,paper,bond paper,ream,print,printing,photocopy,xerox,tuition,thesis,project,folder,marker,calculator,lab,module,exam,school,supplies,binding,lamination,uniform',
        'Utilities & Internet' => 'load,data,wifi,internet,electricity,meralco,prepaid,globe,smart,converge,pldt,rent,boarding house,bills',
        'Entertainment & Leisure' => 'movie,cinema,netflix,spotify,youtube,game,games,mobile legends,valorant,concert,ticket,karaoke,arcade,party,gala,outing,streaming',
        'Personal Care & Health' => 'shampoo,soap,toothpaste,toothbrush,medicine,paracetamol,biogesic,vitamins,haircut,salon,alcohol,mask,deodorant,lotion,cologne,napkin,pads,clinic,checkup',
    ];

    public function up()
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('expense_categories', 'keywords')) {
                $table->text('keywords')->nullable()->after('color');
            }

            if (!Schema::hasColumn('expense_categories', 'is_fallback')) {
                $table->boolean('is_fallback')->default(false)->after('status');
            }
        });

        foreach ($this->defaults as $name => $keywords) {
            DB::table('expense_categories')
                ->where('name', $name)
                ->whereNull('keywords')
                ->update(['keywords' => $keywords]);
        }

        // Flag exactly one existing "Other"-style category as the fallback.
        $fallbackId = DB::table('expense_categories')
            ->whereIn(DB::raw('LOWER(name)'), ['other', 'others', 'misc', 'miscellaneous'])
            ->orderBy('id')
            ->value('id');

        if ($fallbackId) {
            DB::table('expense_categories')->where('id', $fallbackId)->update(['is_fallback' => true]);
        }
    }

    public function down()
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            if (Schema::hasColumn('expense_categories', 'is_fallback')) {
                $table->dropColumn('is_fallback');
            }

            if (Schema::hasColumn('expense_categories', 'keywords')) {
                $table->dropColumn('keywords');
            }
        });
    }
}