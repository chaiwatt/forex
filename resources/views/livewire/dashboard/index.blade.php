@push('styles')
    <style>
        #canndle_stick_chart,
        #rsi_chart,
        #macd_chart {
            width: 96%;
            height: 600px;
            margin: 20px;
            padding: 0;
        }
        #rsi_chart {
            margin: 20px;
            height: 400px;
            width: 96%;
        }
        #macd_chart {
            margin: 20px;
            height: 400px;
            width: 96%;
        }
        /* #atr_chart {
            margin: 20px;
            height: 400px;
            width: 96%;
        } */
        #cci_chart {
            margin: 20px;
            margin-left: 20px;
            height: 400px;
            width: 96%;
        }
        #middle {
            text-align: center;
            padding: 10px;
            background: #d4e8f1;
        }
    </style>
@endpush
<div>
    <div class="card ">
        <div class="card-body">
            <button wire:click="fetchData" type="button" class="btn btn-primary">Refresh</button>
            {{-- <button wire:poll="fetchData" type="button" class="btn btn-primary">Refresh</button> --}}
  {{-- {{$numOfFetch}} --}}

            <div class="row" >
                <div class="col-12">
                    <div id="canndle_stick_chart"></div>
                    <div id="macd_chart"></div>
                    <div id="rsi_chart"></div>
                    <div id="atr_chart"></div>
                    <div id="cci_chart"></div>
                </div>
            </div>
        </div>
      </div>
</div>
@push('js')

<script>
    var upColor = '#00da3c';
    var downColor = '#ec0000';
    var firstRun = true;
    var numOfCross = 0;
    var numOfTick = 0;
    var histogramIndex = 0;
    var sumHistogram = 0;
    var coordOfCrossSMA = [];
    var dataMA5 = [];
    var dataMA10 = [];
    var dataMA20 = [];
  
    var data = [];// Open，Close，Low，Hi
    var axisData = [];

    var MacdArr = [];
    var SignalArr = [];
    var HistogramArr = [];
    var RSIArr = [];
    var RSIDelayArr = [];
    var CCIArr = [];
    var CrossClosedWithHistogram = [];

    var forexData =  @json($data);
    function getAvgClosedPrice(mArray,mRange){
        return mArray.slice(0, mRange).reduce((a,c) => a + c, 0) / mRange;
    }

    function EMACalc(mArray,mRange) {
        var k = 2/(mRange + 1);
        var avgClosed = getAvgClosedPrice(mArray,mRange)
        emaArray = [avgClosed];
        
        var _emaArray = [];
        for (var i = mRange; i < mArray.length+1; i++) {
            emaArray.push(mArray[i] * k + emaArray[i -mRange] * (1 - k));
            _emaArray.push(emaArray[i-mRange]);
        }
        return _emaArray;
    }

    function MACDCalc(mArray1,mArray2,mRange1,mRange2) {
        var diffRange = mRange1 - mRange2;
        var _macdArray = [];
        for (var i = 0; i < mArray1.length; i++) {
            var _macd  = mArray2[i+diffRange] - mArray1[i];
            _macdArray.push(_macd);
        }
        return _macdArray;
    }

    function HistogramCalc(mArray1,mArray2,mRange) {
        var diffRange = mRange - 1;
        var _histogramArray = [];
        for (var i = 0; i < mArray1.length; i++) {
            var _histogram = mArray2[i+diffRange] - mArray1[i];
            _histogramArray.push(_histogram);
        }
        return _histogramArray;
    }

    function getClosedPrice(rawData) {

        var _closedprice = []
        for (var i = 0; i < rawData.length; i++) {
            _closedprice.push(rawData[i][2])
        }
        return _closedprice
    }

    function getLowPrice(rawData) {
        var _lowprice = []
        for (var i = 0; i < rawData.length; i++) {
            _lowprice.push(rawData[i][3])
        }
        return _lowprice
    }

    function getHighPrice(rawData) {
        var _highprice = []
        for (var i = 0; i < rawData.length; i++) {
            _highprice.push(rawData[i][4])
        }
        return _highprice
    }

    function getDateLabel(rawData) {
        var _dateLabel = []
        for (var i = 0; i < rawData.length; i++) {
            _dateLabel.push(rawData[i][0])
        }
        return _dateLabel
    }

    function getData(rawData) {
        var _data = []
        
        for (var i = 0; i < rawData.length; i++) {
            _data.push(Object.seal([rawData[i][1],rawData[i][4],rawData[i][3],rawData[i][2]]))
        }
        return _data
    }

    function calculateMA(dayCount, data) {
        var result = [];
        for (var i = 0, len = data.length; i < len; i++) {
            if (i < dayCount) {
                result.push('-');
                continue;
            }
            var sum = 0;
            for (var j = 0; j < dayCount; j++) {
                sum += data[i - j][1];
            }
            result.push((sum / dayCount));
        }
        return result;
    }

    function RS(mArray,mRange) {
        var _closePriceChanged = [];
        var _closePriceChangedGain = [];
        var _closePriceChangedLost = [];
        var _avgGain = [];
        var _avgLost = [];
        
        var _RS = [];
        for (var i = 1; i < mArray.length; i++) {
            var closePriceChanged  = mArray[i] - mArray[i-1];
            _closePriceChanged.push(closePriceChanged);
            if(closePriceChanged > 0){
                _closePriceChangedGain.push(closePriceChanged);
                _closePriceChangedLost.push(0);
            }else{
                _closePriceChangedGain.push(0);
                _closePriceChangedLost.push(closePriceChanged*-1)
            }
        }

        var avgGain = _closePriceChangedGain.slice(0, mRange).reduce((a,c) => a + c, 0) / mRange;
        var avgLost = _closePriceChangedLost.slice(0, mRange).reduce((a,c) => a + c, 0) / mRange;

        _avgGain = [avgGain];
        _avgLost = [avgLost];


        for (var i = mRange; i < _closePriceChangedGain.length; i++) {
            _avgGain.push((_avgGain[i-mRange]*(mRange-1) + _closePriceChangedGain[i])/mRange );
        }

        for (var i = mRange; i < _closePriceChangedLost.length; i++) {
            _avgLost.push((_avgLost[i-mRange]*(mRange-1) + _closePriceChangedLost[i])/mRange );
        }
        for (var i = 0; i < _avgGain.length; i++) {
            var rs = _avgGain[i] / _avgLost[i];
            if(_avgLost[i] == 0){
                _RS.push(100);
            }else{
                _RS.push(100-(100/(1+rs)));
            }
        }
        return _RS;
    }

    function delayRSI(rsiArr,mRange){
        var _delayAsi = [];
        _delayAsi = [];
        for (var i = 0; i < rsiArr.length-mRange+1; i++) {
                var avgRSI = rsiArr.slice(i, mRange+i).reduce((a,c) => a + c, 0) / mRange;
                _delayAsi.push(avgRSI);
            }
            return _delayAsi;
    }

    function CCICalc(highArray,lowArray,closeArray,mRange) {
        const cciContant = 0.015;
        var typicalPriceArray = [];
        for (var i = 0; i < highArray.length; i++) {
            typicalPriceArray.push((highArray[i] + lowArray[i] + closeArray[i])/3 );
        }
        var sma = typicalPriceArray.slice(0, mRange).reduce((a,c) => a + c, 0) / mRange;

        var _sma = [];
        for (var i = 0; i < typicalPriceArray.length-mRange+1; i++) {
            var avgSma = typicalPriceArray.slice(i, mRange+i).reduce((a,c) => a + c, 0) / mRange;
            _sma.push(avgSma);
        }
        var _meanDeviation = [];
        for (var i = mRange; i < typicalPriceArray.length+1; i++) {
            var sma = _sma[i-mRange];
            var sum_sma = 0;
            var splicTypicalPriceArray = typicalPriceArray.slice(i-mRange,i);
            for (var k = mRange; k < splicTypicalPriceArray.length+mRange; k++) {
                var int_sum = Math.abs(sma - splicTypicalPriceArray[k-mRange]);
                sum_sma += int_sum;
            }
            _meanDeviation.push(sum_sma/mRange);
            
        }
        var _cci = [];
        for (var i = mRange; i < _meanDeviation.length+mRange; i++) {
            _cci.push((typicalPriceArray[i-1] - _sma[i-mRange]) /  (_meanDeviation[i-mRange]*cciContant) );
        }
        return _cci;
    }

    function getLineArray(mArray1,mArray2,mArray3) {
        var diffRange = mArray1.length - mArray2.length;
        var _line = [];
        for (var i = diffRange; i < mArray1.length; i++) {
            _line.push((mArray2[i-diffRange] - mArray1[i])*100/mArray2[i-diffRange]);
        }


        var nullLine = Array(mArray3.length - _line.length).fill(null);
        var _lineArray = nullLine.concat(_line); 
        return _lineArray;
    }

    function getSignChange(arr){
        let positive = arr[0] >= 0; 
        return arr.map((item, index) => {
            if ((positive && item < 0 || !positive && item >= 0)) {
                positive = arr[index] >= 0
                if(arr[index-1]!==null && item !== null){
                    return [index-1, arr[index-1], item]
                }
            }
        
        }).filter(x => x != null);
    }


    function crossListCalc(arr1,arr2){
        var _crossList = [];
        for (var i = 0; i < arr2.length; i++) {
            var label = 'Buy';
            if(arr2[i][1] > arr2[i][2]){
                    label = 'Sale';
            }
            _crossList[i] = {
                        name: label,
                        value: arr1[arr2[i][0]+1],
                        xAxis: arr2[i][0],
                        yAxis: arr1[arr2[i][0]+1]
                };
        }
        return _crossList;
    }

    function crossSMA(shortSMA,longSMA){
        var xValue = 0;
        var yValue = 0;
        var start = 0; //buy
        if(shortSMA[shortSMA.length-1] > longSMA[longSMA.length-1] ){
            start = 1; //sale
            console.log('start with sale')
        }else{
            console.log('start with buy')
        }
        if(start == 0){
            for(var i = longSMA.length-1 ; i > 0 ; i--){
                if(shortSMA[i] > longSMA[i]){
                    break;
                }
                xValue = i;
                yValue = shortSMA[i];
            }
        }else{
            for(var i = longSMA.length-1 ; i > 0 ; i--){
                if(shortSMA[i] < longSMA[i]){
                    break;
                }
                xValue = i;
                yValue = longSMA[i];
            }
            
        }
        console.log('X:'+xValue + ' Y:' + yValue)
    }

        //   var colorList = ['#c23531','#2f4554', '#61a0a8', '#d48265', '#91c7ae','#749f83',  '#ca8622', '#bda29a','#6e7074', '#546570', '#c4ccd3'];

    function createData(_forexData){
        var closedPrice = [];
        var lowPrice = [];
        var highPrice = [];
        var EMA12 = [];
        var EMA26 = [];
        var MACD = [];
        var SIGNAL = [];
        var HISTOGRAM = [];
        var RSI = [];
        var DelayRSI = [];
        var CCI = [];
        data = getData(_forexData);
        closedPrice = getClosedPrice(_forexData);
        lowPrice = getLowPrice(_forexData);
        highPrice = getHighPrice(_forexData);
        axisData = getDateLabel(_forexData); 
        
        EMA12 = EMACalc(closedPrice,12);
        EMA26 = EMACalc(closedPrice,26);
        MACD = MACDCalc(EMA26,EMA12,26,12);
        SIGNAL = EMACalc(MACD,9);
        HISTOGRAM = HistogramCalc(SIGNAL,MACD,9);
        RSI = RS(closedPrice,14);
        DelayRSI = delayRSI(RSI,5);
        CCI = CCICalc(highPrice,lowPrice,closedPrice,20);
        
        // console.log(SIGNAL);
        // console.log(HISTOGRAM);

        var nullForMACD = Array(closedPrice.length - MACD.length).fill(null);
        var nullForSIGNAL = Array(closedPrice.length - SIGNAL.length).fill(null);
        var nullForHISTOGRAM = Array(closedPrice.length - HISTOGRAM.length).fill(null);
        var nullRS = Array(closedPrice.length - RSI.length).fill(null);
        var nullRSDELAY = Array(closedPrice.length - DelayRSI.length).fill(null);
        var nullCCI = Array(closedPrice.length - CCI.length).fill(null);

         dataMA5 = calculateMA(5, data);
         dataMA10 = calculateMA(10, data);
        //  dataMA20 = calculateMA(20, data);

        console.log(dataMA10);

         MacdArr = nullForMACD.concat(MACD); 
         SignalArr = nullForSIGNAL.concat(SIGNAL); 
         HistogramArr = nullForHISTOGRAM.concat(HISTOGRAM); 
         RSIArr = nullRS.concat(RSI); 
         RSIDelayArr = nullRSDELAY.concat(DelayRSI); 
         CCIArr = nullCCI.concat(CCI); 
         CrossClosedWithHistogram = crossListCalc(closedPrice,getSignChange(HistogramArr)); 
         

         var hisTogramData = getSignChange(HistogramArr);
         var previousSum = 0;
         if(numOfTick != 0){
            numOfTick ++;
            histogramIndex ++
            sumHistogram += HistogramArr[histogramIndex]
         }

         if(firstRun == true){
            numOfCross = hisTogramData.length;
            firstRun = false;
            coordOfCrossSMA = crossSMA(dataMA5,dataMA10);
         }else{
             if(hisTogramData.length > numOfCross){
                 if(sumHistogram !== 0){
                    previousSum = sumHistogram;
                 }
                
                sumHistogram = 0;
                numOfCross = hisTogramData.length;
                numOfTick = 1;
                
                histogramIndex = hisTogramData[hisTogramData.length-1][0]
                histogramIndex ++;
                sumHistogram += HistogramArr[histogramIndex]

                previousSum += sumHistogram*-1;
                console.log('ผลรวม History:' + ' ' + previousSum);

             }
         }

         console.log('นับ:' + numOfTick + ' ผลรวม History:' + sumHistogram + ' Macd:' + MacdArr[histogramIndex]);

    }

    document.addEventListener('livewire:load', () =>{
        @this.on('refreshChart', (chartData) => {
            createData(chartData);
            canndleStickChart.setOption({
                series : [
                    {
                        data: data,
                    }, {
                        name: 'MA5',
                        data: dataMA5,
                    }, {
                        name: 'MA10',
                        data: dataMA10,
                    }
                    // , {
                    //     name: 'MA20',
                    //     data: dataMA20,
                    // }
                ],                
                xAxis : [
                    {
                        data : axisData
                    }
                ],
                dataZoom : {
                    start : Math.round((data.length-100)/(data.length)*100),
                    end : 100
                },
     

            });
            rsiChart.setOption({
                series : [
                    {
                        name:'RSI',

                        data: RSIArr,
                    },
                    {
                        name:'RSIDELAY',
                        data: RSIDelayArr,
                    }
                ],                
                xAxis : [
                    {
                        data : axisData
                    }
                ],
                dataZoom : {
                    start : Math.round((data.length-100)/(data.length)*100),
                    end : 100
                },
            });
            macdChart.setOption({
                series : [
                    {
                        name:'MACD',
                        data: MacdArr,
                    },{
                        name:'SIGNAL',
                        data: SignalArr,
                    },{
                        name:'HISTOGRAM',
                        data: HistogramArr,
                    }
                ],                
                xAxis : [
                    {
                        data : axisData
                    }
                ],
                dataZoom : {
                    start : Math.round((data.length-100)/(data.length)*100),
                    end : 100
                },
            });
            cciChart.setOption({
                series : [
                    {
                        name:'CCI',
                        data: CCIArr,
                    }
                ],                
                xAxis : [
                    {
                        data : axisData
                    }
                ],
                dataZoom : {
                    start : Math.round((data.length-100)/(data.length)*100),
                    end : 100
                },
            });
        });
    });
    
    createData(forexData);
    

            option = {
                backgroundColor: '#21202D',
                title : {
                    text: 'USDJYP',
                    textStyle: {
                        color: '#fff'
                    }
                },
                tooltip : {
                    trigger: 'axis',
                    showDelay: 0,             // delay ms
                    formatter: function (params) {
                        var res = params[0].name;
                        res += '<br/>' + params[0].seriesName;
                        res += '<br/>  Open : ' + params[0].value[1] + '  High : ' + params[0].value[4];
                        res += '<br/>  Close : ' + params[0].value[2] + '  Low : ' + params[0].value[3];
                        return res;
                    }
                },
                legend: {
                    data:['USDJYP','MA5', 'MA10'],
                    textStyle: {
                        color: '#fff'
                    }
                },
                toolbox: {
                    show : true,
                    feature : {
                        mark : {show: true},
                        dataZoom : {show: true},
                        magicType : {show: true, type: ['line', 'bar']},
                        restore : {show: true},
                        saveAsImage : {show: true}
                    }
                },            
                dataZoom : {
                    y: 250,
                    show : false,
                    realtime: true,
                    start : Math.round((data.length-100)/(data.length)*100),
                    end : 100
                },
                grid: {
                    left: 80,
                    top: 40,
                    right: 20,
                    bottom: 85
                },
                xAxis : [
                    {
                        type : 'category',
                        boundaryGap : true,
                        axisTick: {onGap:false},
                        splitLine: {show:false},
                        data : axisData
                    }
                ],
                yAxis : [
                    {
                        type : 'value',
                        scale:true,
                        boundaryGap: [0.05, 0.05],
                        splitArea : {show : true}
                    }
                ],
                series : [
                    {
                        name:'USDJYP',
                        type:'candlestick',
                        data: data,
                        // markPoint: {
                        //     label: {
                        //         normal: {
                        //             formatter: function (param) {
                        //                 return param != null ? param.data['name'] : '';
                        //             }
                        //         }
                        //     },
                        //     data: CrossClosedWithHistogram,
                        // },
                        itemStyle: {
                            color: upColor,
                            color0: downColor,
                            borderColor: null,
                            borderColor0: null
                        },
                    }, {
                        name: 'MA5',
                        type: 'line',
                        data: dataMA5,
                        smooth: true,
                        showSymbol: false,
                        lineStyle: {
                            width: 1
                        }
                    }, {
                        name: 'MA10',
                        type: 'line',
                        data: dataMA10,
                        smooth: true,
                        showSymbol: false,
                        lineStyle: {
                            width: 1
                        }
                    }, 
                    // {
                    //     name: 'MA20',
                    //     type: 'line',
                    //     data: dataMA20,
                    //     smooth: true,
                    //     showSymbol: false,
                    //     lineStyle: {
                    //         width: 1
                    //     }
                    // }
                ]
            };

            option_rsi = {
                backgroundColor: '#21202D',
                tooltip : {
                    trigger: 'axis',
                    showDelay: 0             // delay ms
                },
                legend: {
                    // y : -30,
                    data:['RSI','RSIDELAY'],
                    textStyle: {
                        color: '#fff'
                    }
                },
                toolbox: {
                    y : -30,
                    show : true,
                    feature : {
                        mark : {show: true},
                        dataZoom : {show: true},
                        dataView : {show: true, readOnly: false},
                        magicType : {show: true, type: ['line', 'bar']},
                        restore : {show: true},
                        saveAsImage : {show: true}
                    }
                },
                dataZoom : {
                    show : false,
                    realtime: true,
                    start : Math.round((data.length-100)/(data.length)*100),
                    end : 100
                },
                grid: {
                    x: 80,
                    y: 20,
                    x2: 20,
                    y2: 60
                },
                xAxis : [
                    {
                        show : false,
                        type : 'category',
                        position:'top',
                        // boundaryGap : true,
                        splitLine: {show:false},
                        data : axisData
                    }
                ],
                yAxis : [
                    {
                        type : 'value',
                        scale:true,
                        splitNumber: 3,
                        boundaryGap: [0.05, 0.05],
                        // axisLabel: {
                        //     formatter: function (v) {
                        //         return Math.round(v/10000) + ' 万'
                        //     }
                        // },
                        splitArea : {show : true}
                    }
                ],
                series : [//
                    {
                        name:'RSI',
                        type:'line',
                        symbol: 'none',
                        data: RSIArr,
                        markLine : {
                            symbol : 'none',
                            itemStyle : {
                                normal : {
                                    color:'#1e90ff',
                                    label : {
                                        show:true
                                    }
                                }
                            },
                            data : [
                                { 
                                    name: 'Sell', 
                                    yAxis: 70,
                                    lineStyle: {
                                        color: '#14b143'
                                    },
                                },{ 
                                    name: 'Buy', 
                                    yAxis: 30,
                                    lineStyle: {
                                        color: '#ef232a'
                                    },
                                }
                            ]
                        }
                    },
                    {
                        name:'RSIDELAY',
                        type:'line',
                        symbol: 'none',
                        data: RSIDelayArr,
                    }
                ]
            };

            option_macd = {
                backgroundColor: '#21202D',
                tooltip : {
                    trigger: 'axis',
                    showDelay: 0             // delay ms
                },
                legend: {
                    // y : -30,
                    data:['MACD','SIGNAL','HISTOGRAM'],
                    textStyle: {
                        color: '#fff'
                    }
                },
                toolbox: {
                    y : -30,
                    show : true,
                    feature : {
                        mark : {show: true},
                        dataZoom : {show: true},
                        dataView : {show: true, readOnly: false},
                        magicType : {show: true, type: ['line', 'bar']},
                        restore : {show: true},
                        saveAsImage : {show: true}
                    }
                },
                dataZoom : {
                    show : false,
                    realtime: true,
                    start : Math.round((data.length-100)/(data.length)*100),
                    end : 100
                },
                grid: {
                    x: 80,
                    y: 20,
                    x2: 20,
                    y2: 60
                },
                xAxis : [
                    {
                        show : false,
                        type : 'category',
                        position:'top',
                        // boundaryGap : true,
                        splitLine: {show:false},
                        data : axisData
                    }
                ],
                yAxis : [
                    {
                        type : 'value',
                        scale:true,
                        splitNumber: 3,
                        boundaryGap: [0.05, 0.05],
                        splitArea : {show : true}
                    }
                ],
                series : [
                    {
                        name:'MACD',
                        type:'line',
                        symbol: 'none',
                        data: MacdArr,
                    },{
                        name:'SIGNAL',
                        type:'line',
                        symbol: 'none',
                        data: SignalArr,
                    },{
                        name:'HISTOGRAM',
                        type:'bar',
                        symbol: 'none',
                        data: HistogramArr,
                    }
                ]
            };

            option_CCI = {
                backgroundColor: '#21202D',
                tooltip : {
                    trigger: 'axis',
                    showDelay: 0             // delay ms
                },
                legend: {
                    data:['CCI'],
                    textStyle: {
                        color: '#fff'
                    }
                },
                toolbox: {
                    y : -30,
                    show : true,
                    feature : {
                        mark : {show: true},
                        dataZoom : {show: true},
                        dataView : {show: true, readOnly: false},
                        magicType : {show: true, type: ['line', 'bar']},
                        restore : {show: true},
                        saveAsImage : {show: true}
                    }
                },
                dataZoom : {
                    bottom: 5,
                    show : true,
                    realtime: true,
                    start : Math.round((data.length-100)/(data.length)*100),
                    end : 100
                },
                grid: {
                    x: 80,
                    y: 20,
                    x2: 20,
                    y2: 80
                },
                xAxis : [
                    {
                        show : false,
                        type : 'category',
                        position:'top',
                        // boundaryGap : true,
                        splitLine: {show:false},
                        data : axisData
                    }
                ],
                yAxis : [
                    {
                        type : 'value',
                        scale:true,
                        splitNumber: 3,
                        boundaryGap: [0.05, 0.05],
                        splitArea : {show : true}
                    }
                ],
                series : [
                    {
                        name:'CCI',
                        type:'line',
                        symbol: 'none',
                        data: CCIArr,
                        markLine : {
                            symbol : 'none',
                            itemStyle : {
                                normal : {
                                    color:'#1e90ff',
                                    label : {
                                        show:true
                                    }
                                }
                            },
                            data : [
                                { 
                                    name: 'Hi', 
                                    yAxis: 100,
                                    lineStyle: {
                                        color: '#14b143'
                                    },
                                },{ 
                                    name: 'Low', 
                                    yAxis: -100,
                                    lineStyle: {
                                        color: '#ef232a'
                                    },
                                }
                            ]
                        }
                    }
                ]
            };

            var canndleStickChart = echarts.init(document.getElementById('canndle_stick_chart'));  
            var rsiChart = echarts.init(document.getElementById('rsi_chart')); 
            var macdChart = echarts.init(document.getElementById('macd_chart')); 
            var cciChart = echarts.init(document.getElementById('cci_chart'));

            canndleStickChart.setOption(option);
            rsiChart.setOption(option_rsi);
            macdChart.setOption(option_macd);
            cciChart.setOption(option_CCI);

            echarts.connect([canndleStickChart, rsiChart, macdChart, cciChart]);

            window.onresize = function () {
                canndleStickChart.resize();
                rsiChart.resize();
                macdChart.resize();
                cciChart.resize();
            }

 
    </script>
@endpush
