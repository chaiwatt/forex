<?php

namespace App\Http\Livewire\Dashboard;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\StockData;
use Illuminate\Support\Str;

class Index extends Component
{
    public $data = [];

    public $numOfFetch ;

    protected $listeners = [
        'getImage' => 'getChartImage',
        'saveImage' => 'saveChartImage',
    ];

    public function mount(){
        $this->numOfFetch = 200;
        $this->data = StockData::take($this->numOfFetch)->get()->map(fn($item) => [$item-> date, $item->open, $item->high, $item->low, $item->close]);
        // $this->data = StockData::get()->map(fn($item) => [$item-> date, $item->open, $item->high, $item->low, $item->close]);
        // $this->data = StockData::where('id', '>', 2000)->where('id', '<', 3000)->get()->map(fn($item) => [$item-> date, $item->open, $item->high, $item->low, $item->close]);
    }

    public function getChartImage(){
        $this->emitTo('dashboard.index', 'getImage');
    }

    public function saveChartImage($candlestickImage,$macdImage){
        $time = Carbon::now()->format('Y-m-d_H-i');
        $folderPath = public_path() .'/storage/uploads/';
        $image_parts = explode(";base64,", $candlestickImage);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_base64 = base64_decode($image_parts[1]);
        $fname=$time .'_candle.png';
        // $filelocation = "storage/uploads/".$fname;

        $imageFullPath = $folderPath.$fname;
 
        file_put_contents($imageFullPath, $image_base64);


        $image_parts = explode(";base64,", $macdImage);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_base64 = base64_decode($image_parts[1]);
        $fname=$time .'_macd.png';
        // $filelocation = "storage/uploads/".$fname;

        $imageFullPath = $folderPath.$fname;
 
        file_put_contents($imageFullPath, $image_base64);
 
    }

    public function fetchData(){
        $this->numOfFetch += 1;
        $this->data = StockData::take($this->numOfFetch)->get()->map(fn($item) => [$item-> date, $item->open, $item->high, $item->low, $item->close]);
        $this->emit('refreshChart',$this->data);
    }

    public function render()
    {
        return view('livewire.dashboard.index');
    }
}
