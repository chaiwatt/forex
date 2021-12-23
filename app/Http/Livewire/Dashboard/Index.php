<?php

namespace App\Http\Livewire\Dashboard;

use Livewire\Component;
use App\Models\StockData;

class Index extends Component
{
    public $data = [];
    public $numOfFetch ;

    public function mount(){
        $this->numOfFetch = 135;
        $this->data = StockData::take($this->numOfFetch)->get()->map(fn($item) => [$item-> date, $item->open, $item->high, $item->low, $item->close]);
        // $this->data = StockData::get()->map(fn($item) => [$item-> date, $item->open, $item->high, $item->low, $item->close]);
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
