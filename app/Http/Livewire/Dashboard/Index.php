<?php

namespace App\Http\Livewire\Dashboard;

use Livewire\Component;
use App\Models\StockData;

class Index extends Component
{
    public $data = [];
    public $numOfFetch ;

    public function mount(){
        $this->numOfFetch = 120;
        $this->data = StockData::take($this->numOfFetch)->get()->map(fn($item) => [$item-> date, $item->open, $item->close, $item->low, $item->high]);
    }

    public function fetchData(){
        $this->numOfFetch += 1;
        $this->data = StockData::take($this->numOfFetch)->get()->map(fn($item) => [$item-> date, $item->open, $item->close, $item->low, $item->high]);
        $this->emit('refreshChart',$this->data);
    }

    public function render()
    {
        return view('livewire.dashboard.index');
    }
}
