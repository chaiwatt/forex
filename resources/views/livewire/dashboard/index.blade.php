@push('styles')
    <style>
        #canndle_stick_chart,
        #rsi_chart,
        #macd_chart {
            width: 96%;
            height: 800px;
            margin: 20px;
            padding: 0;
        }
        #canndle_stick_chart_zoom {
            margin: 20px;
            height: 800px;
            width: 96%;
        }
        #rsi_chart {
            margin: 20px;
            height: 800px;
            width: 96%;
        }
        #macd_chart {
            margin: 20px;
            height: 800px;
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
            
            
            
            {{-- <div id="result" style="width:200px; background:rgb(58, 6, 94)"><button wire:poll.200ms="fetchData" type="button" class="btn btn-primary">Refresh</button></div> --}}
  {{-- {{$numOfFetch}} --}}
            {{-- <button lass="btn btn-info" wire:click="$emit('getImage')">Save img</button> --}}
            <div class="row" >
                <div class="col-12">
                    <div id="canndle_stick_chart"></div>
                   
                    
                    <div id="macd_chart"></div>
                        <div style="width:200px; display:flex">
                            <button wire:click="fetchData" type="button" class="btn btn-primary mr-2">Refresh</button>
                            <div class="mr-2" id="sma100_trend" style="width:100px;">SMA</div>
                            <div class="" id="macd_trend" style="width:100px; ">MACD</div>
                        </div>

                        {{-- <div style="width:200px; display:flex" wire:poll.200ms="fetchData" >
                            <div class="mr-2" id="sma100_trend" style="width:100px;">SMA</div>
                            <div class="" id="macd_trend" style="width:100px; ">MACD</div>
                        </div> --}}
                        <div id="rsi_chart"></div>
                    <div id="canndle_stick_chart_zoom"></div>
                    
                    {{-- <div id="atr_chart"></div>
                    <div id="cci_chart"></div> --}}
                </div>
                {{-- <img id="captureCandle" src="" alt=""> --}}
            </div>
        </div>
      </div>
</div>
@push('js')



<script>

    const usdjpyMacdDiff = 0.000586674;
    const usdjpySumHistogram = 0.002771262;
    var upColor = '#00da3c';
    var downColor = '#ec0000';
    var firstRun = true;
    var numOfCross = 0;
    var numOfTick = 0;
    var histogramIndex = 0;
    var sumHistogram = 0;
    var coordOfCross = [];
    var dataMA5 = [];
    var dataMA10 = [];
    var dataMA20 = [];
    var dataMA50 = [];
    var dataMA100 = [];
    var dataMA200 = [];
  
    var data = [];// Open???Close???Low???Hi
    var axisData = [];

    var MacdArr = [];
    var AvgMacdArr = [];
    var SignalArr = [];
    var HistogramArr = [];
    var RSIArr = [];
    var RSIDelayArr = [];
    var CCIArr = [];
    var SSMA5Arr = [];
    var SSMA8Arr = [];
    var SSMA13Arr = [];
    var SSMA20Arr = [];
    var SSMA50Arr = [];
    var SSMA100Arr = [];
    var Smoth_SSMA5Arr = [];
    var Smoth_SSMA8Arr = [];
    var Smoth_SSMA13Arr = [];
    var Smoth_SSMA20Arr = [];
    var Smoth_SSMA50Arr = [];
    var CrossClosedWithHistogram = [];
    var onOrder = false;
    var macdAtCross = [];
    var EMA5Arr = [];
    var EMA20Arr = [];
    var EMA50Arr = [];
    var EMA200Arr = [];
    var ClosedData = [];
    var HistogramData = [];
    var MacdData = [];
    var MarkData = [];
    var MacdCrossData  = []
    // var markLineCord = [[148, 109.4],[163,109.3]]
    var markLineCord = []
    var markMA100LineCord = []
    var MarkMA100Slope  = []

    var markLineCordSsma5 = []
    var markLineCordSsma8 = []
    var markLineCordSsma13 = []

    var firstCheck = true;
    var forexData =  @json($data);
    var nowUptrend = false;
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
            _closedprice.push(rawData[i][4])
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
            _highprice.push(rawData[i][2])
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

    function getSignChange(arr,macdarr){
        let positive = arr[0] >= 0; 
        return arr.map((item, index) => {
            if ((positive && item < 0 || !positive && item >= 0)) {
                positive = arr[index] >= 0
                if(arr[index-1]!==null && item !== null){
                    var isUpTrendUpperMacd = '';
                    var macdatpoint = macdarr[index];
                    if(item > 0){
                        if(macdatpoint < 0){
                            isUpTrendUpperMacd = 'under';
                        }else{
                            isUpTrendUpperMacd = 'above';
                        }
                    }
                    return [index-1, arr[index-1], item,isUpTrendUpperMacd]
                }
            }
        }).filter(x => x != null);
    }


    function crossListCalc(arr1,arr2){
        var _crossList = [];
        for (var i = 0; i < arr2.length; i++) {
            var label = '????????????';
            if(arr2[i][1] > arr2[i][2]){
                    label = '?????????';
            }
            _crossList[i] = {
                        name: label,
                        value: arr1[arr2[i][0]+1],
                        xAxis: arr2[i][0],
                        yAxis: arr1[arr2[i][0]+1],
                        color: '#000'
                       
                };
        }
        return _crossList;
    }

    function getMacdCross(macd,signal){
        let isUp = true;
        let crossAbove = true;   
        if(macd[macd.length-1] > signal[signal.length-1]){ 
            for(var i = macd.length-1 ; i > 0 ; i--){
                if(macd[i] < signal[i]){
                    if(macd[i] < 0){
                        crossAbove = false
                    }
                    break;
                }
            }
        }else{
            isUp = false
            for(var i = macd.length-1 ; i > 0 ; i--){
                if(macd[i] > signal[i]){
                    if(macd[i] < 0){
                        crossAbove = false
                    }
                    break;
                }
            }
        }
        return [isUp,crossAbove]
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
        // console.log('X:'+xValue + ' Y:' + yValue)
    }

    function everageMacd(macdArr,mRange) {
        _avgMacd = [];
        for (var i = 0; i < macdArr.length-mRange+1; i++) {
            var avgMacd = macdArr.slice(i, mRange+i).reduce((a,c) => a + c, 0) / mRange;
            _avgMacd.push(avgMacd);
        }
        return _avgMacd;
    }

    function StandardDeviationCalc(_array,nRange) {
        // const n = array.length
        let array = _array.slice((_array.length - nRange), _array.length)
        console.log(array);
        const mean = array.reduce((a, b) => a + b) / nRange
        return Math.sqrt(array.map(x => Math.pow(x - mean, 2)).reduce((a, b) => a + b) / nRange)
    }

    
    function markList(arr1){
        var _crossList = [];
        for (var i = 0; i < arr2.length; i++) {
            var label = '????????????';
            if(arr2[i][1] > arr2[i][2]){
                    label = '?????????';
            }
            _crossList[i] = {
                        name: label,
                        value: arr1[arr2[i][0]+1],
                        xAxis: arr2[i][0],
                        yAxis: arr1[arr2[i][0]+1],
                        color: '#000'
                };
        }
        return _crossList;
    }

    function SSMA_Calc(arr,n){
        var ssma = [];
        ssma.push(avarageSum(arr,n));

        for (var i = 1 ; i < arr.length ; i++){
            ssma.push((ssma[i-1]*(n-1) + arr[i])/n);
        }
        return ssma;

        function avarageSum(arr,n){
            var temp = arr.slice(0, n);
            return temp.reduce(function(a,b){return a+b;})/temp.length;
        }
    }

    function SSMA_BARESMOTH(arrSSMA,mRange) {
        _ssmaSmoth = [];
        for (var i = 0; i < arrSSMA.length-mRange+1; i++) {
            var ssmaSmoth = arrSSMA.slice(i, mRange+i).reduce((a,c) => a + c, 0) / mRange;
            _ssmaSmoth.push(ssmaSmoth);
        }
        return _ssmaSmoth;
    }

    function getCrossPoint(arr1,arr2){
        let reverseValue = false
        for(var i = arr2.length-1 ; i > 0 ; i--){
            if(arr2[arr2.length-1] > arr1[arr2.length-1]){
                if(arr2[i] < arr1[i]){
                    return i
                }
            }else if(arr2[0] < arr1[0]){
                if(arr2[i] > arr1[i]){
                    reverseValue = true;
                }
                if(reverseValue == true){
                    if(arr2[i] < arr1[i]){
                        return i
                    }
                }
            }
        }
    }

    


    function genRegressionLine(_data,nRange){
        var yVal = [];
        for(var i = _data.length-nRange ; i < _data.length ; i++ ){
            yVal.push(_data[i]);
        }
        const xVal = Array(nRange ).fill().map((_, idx) => 1 + idx)
      
        const mX = xVal.reduce((a,v,i)=>(a*i+v)/(i+1));
        const mY = yVal.reduce((a,v,i)=>(a*i+v)/(i+1));

        let xValMinusMx = xVal.map(function(val){
            return  (val - mX)
        })

        let xValMinusMxSquare = xValMinusMx.map(function(val){
            return  val*val
        })

        let yValMinusMy = yVal.map(function(val){
            return  (val - mY)
        })
      
        let diffMxTimediffMy = yValMinusMy.map(function(val,index){
            return val * xValMinusMx[index]
        })

        const sumSquareError = xValMinusMxSquare.reduce((a, b) => a + b, 0)

        const sumdiffMxTimediffMy = diffMxTimediffMy.reduce((a, b) => a + b, 0)
        
        let slope = sumdiffMxTimediffMy/sumSquareError

        let constantC = mY - mX*slope

        return [slope,constantC]
    }

    function getCoord(arr,startX,endX,nRage){
        let startY = 1 * arr[0] + arr[1]
        let endY = nRage * arr[0] + arr[1]
        return [[startX,startY],[endX,endY]]
    }

    function isUpTrend(slope,_data,sma100,nRange,guage){
        let tmp = [
                {
                name: {
                    slope: 'Slope: ' + slope
                },
                value: sma100[_data.length-1],
                xAxis: _data.length-1,
                yAxis: sma100[_data.length-1],
                color: "#000",
            }
        ]
        var allSMA100Low = true;
        for(var i = _data.length-nRange ; i < _data.length ; i++ ){
            if(sma100[i] > _data[i][2]){
                allSMA100Low = false
                return [tmp, false]
            }        
        }
        if(allSMA100Low == true && slope > guage){
            return [tmp, true]
        }else{
            return [tmp, false]
        }
    }

    function isSssmaSequence(ssma5,ssma8,ssma13){
        if((ssma5[ssma5.length-1] > ssma8[ssma8.length-1]) && (ssma5[ssma5.length-1] > ssma13[ssma13.length-1])){
            return true
        }
        return false
    }


        //   var colorList = ['#c23531','#2f4554', '#61a0a8', '#d48265', '#91c7ae','#749f83',  '#ca8622', '#bda29a','#6e7074', '#546570', '#c4ccd3'];
    function createData(_forexData){
        var closedPrice = [];
        var lowPrice = [];
        var highPrice = [];
        var EMA_SHORT = [];
        var EMA_LONG = [];
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
        
        EMA_SHORT = EMACalc(closedPrice,12);
        EMA_LONG = EMACalc(closedPrice,26);
        EMA5Arr = EMACalc(closedPrice,5);
        EMA20Arr = EMACalc(closedPrice,20);
        EMA50Arr = EMACalc(closedPrice,50);
        EMA200Arr = EMACalc(closedPrice,200);
        MACD = MACDCalc(EMA_LONG,EMA_SHORT,26,12);
        SSMA5Arr = SSMA_Calc(closedPrice,5);
        SSMA8Arr = SSMA_Calc(closedPrice,8);
        SSMA13Arr = SSMA_Calc(closedPrice,13);
        SSMA20Arr = SSMA_Calc(closedPrice,20);
        SSMA50Arr = SSMA_Calc(closedPrice,50);

        Smoth_SSMA5 = SSMA_BARESMOTH(SSMA5Arr,3);
        Smoth_SSMA8 = SSMA_BARESMOTH(SSMA8Arr,3);
        Smoth_SSMA13 = SSMA_BARESMOTH(SSMA13Arr,3);
        Smoth_SSMA20 = SSMA_BARESMOTH(SSMA20Arr,3);
        Smoth_SSMA50 = SSMA_BARESMOTH(SSMA50Arr,3);
      
        // SSMA100Arr = SSMA_Calc(closedPrice,100);
        // AVGMACD = everageMacd(MACD,3);
        SIGNAL = EMACalc(MACD,9);
        HISTOGRAM = HistogramCalc(SIGNAL,MACD,9);


        RSI = RS(closedPrice,14);
        DelayRSI = delayRSI(RSI,2);
        // CCI = CCICalc(highPrice,lowPrice,closedPrice,20);
        
        // console.log(SIGNAL);
        // console.log(HISTOGRAM);

        var nullForMACD = Array(closedPrice.length - MACD.length).fill(null);
        var nullForSIGNAL = Array(closedPrice.length - SIGNAL.length).fill(null);
        var nullForHISTOGRAM = Array(closedPrice.length - HISTOGRAM.length).fill(null);
        var nullRS = Array(closedPrice.length - RSI.length).fill(null);
        var nullRSDELAY = Array(closedPrice.length - DelayRSI.length).fill(null);
        // var nullCCI = Array(closedPrice.length - CCI.length).fill(null);
        // var nullAVGMACD = Array(closedPrice.length - AVGMACD.length).fill(null);
        var nullSmoth_SSMA5 = Array(closedPrice.length - Smoth_SSMA5.length).fill(null);
        var nullSmoth_SSMA8 = Array(closedPrice.length - Smoth_SSMA8.length).fill(null);
        var nullSmoth_SSMA13 = Array(closedPrice.length - Smoth_SSMA13.length).fill(null);
        var nullSmoth_SSMA20 = Array(closedPrice.length - Smoth_SSMA20.length).fill(null);
        var nullSmoth_SSMA50 = Array(closedPrice.length - Smoth_SSMA50.length).fill(null);
        // console.log(SIGNAL);

         dataMA5 = calculateMA(5, data);
         dataMA10 = calculateMA(10, data);
         dataMA20 = calculateMA(20, data);
         dataMA50 = calculateMA(50, data);
         dataMA100 = calculateMA(100, data);
         dataMA200 = calculateMA(200, data);

         MacdArr = nullForMACD.concat(MACD); 
         SignalArr = nullForSIGNAL.concat(SIGNAL); 
         HistogramArr = nullForHISTOGRAM.concat(HISTOGRAM); 

         Smoth_SSMA5Arr = nullSmoth_SSMA5.concat(Smoth_SSMA5); 
         Smoth_SSMA8Arr = nullSmoth_SSMA8.concat(Smoth_SSMA8); 
         Smoth_SSMA13Arr = nullSmoth_SSMA13.concat(Smoth_SSMA13); 
         Smoth_SSMA20Arr = nullSmoth_SSMA20.concat(Smoth_SSMA20); 
         Smoth_SSMA50Arr = nullSmoth_SSMA8.concat(Smoth_SSMA50); 
         Ssma5CrossSsma8Index = getCrossPoint(Smoth_SSMA8Arr,Smoth_SSMA5Arr)+1;
         Ssma5CrossSsma13Index = getCrossPoint(Smoth_SSMA13Arr,Smoth_SSMA5Arr)+1;


         MacdCrossData = getMacdCross(MacdArr,SignalArr)
        //  console.log(MacdCrossData)
         RSIArr = nullRS.concat(RSI); 
         RSIDelayArr = nullRSDELAY.concat(DelayRSI); 
         console.log(isSssmaSequence(Smoth_SSMA5Arr,Smoth_SSMA8Arr,Smoth_SSMA13Arr))  
        //  console.log('?????????SSMA8: ' + Ssma5CrossSsma8Index + ' ?????????SSMA13:' + Ssma5CrossSsma13Index);
                // console.log((Ssma5CrossSsma8Index-1) + ' ' + (Ssma5CrossSsma8Index-1 - 15));)
        // let diffCross =         
        // if((Ssma5CrossSsma13Index - Ssma5CrossSsma8Index) <= 1){
        //     console.log('?????????????????????');
        // }

      console.log(StandardDeviationCalc(Smoth_SSMA5Arr,5))  
      console.log(StandardDeviationCalc(Smoth_SSMA5Arr,5))  
      console.log(StandardDeviationCalc(Smoth_SSMA5Arr,5))  
        
         if(firstCheck == true){
            if(typeof Smoth_SSMA5Arr[Ssma5CrossSsma8Index] === 'undefined' ){
                
                firstCheck = false
            }
         }else{
            if(typeof Smoth_SSMA5Arr[Ssma5CrossSsma8Index] !== 'undefined' ){
                // console.log('SSMA5: ' + Smoth_SSMA5Arr[Ssma5CrossSsma8Index] + ' SSMA8:' + Smoth_SSMA8Arr[Ssma5CrossSsma8Index]);
                // console.log((Ssma5CrossSsma8Index-1) + ' ' + (Ssma5CrossSsma8Index-1 - 15));
                // let startX = Ssma5CrossSsma8Index - 15
                // let endX = Ssma5CrossSsma8Index
                // let regressiveEq = genRegressionLine(Ssma5CrossSsma8Index,data,15)
                // markLineCord = getCoord(regressiveEq,startX,endX,15)
            }
         }

        //  console.log(dataMA100[dataMA100.length-1])

         let regressiveEq = genRegressionLine(dataMA100,30)
         markMA100LineCord = getCoord(regressiveEq,dataMA100.length-1-30,dataMA100.length-1,30)


        let regressiveEqSmma5 = genRegressionLine(Smoth_SSMA5Arr,5)
        markLineCordSsma5 = getCoord(regressiveEqSmma5,Smoth_SSMA5Arr.length-1-5,Smoth_SSMA5Arr.length-1,5)

        let regressiveEqSmma8 = genRegressionLine(Smoth_SSMA8Arr,5)
        markLineCordSsma8 = getCoord(regressiveEqSmma8,Smoth_SSMA8Arr.length-1-5,Smoth_SSMA8Arr.length-1,5)

        let regressiveEqSmma13 = genRegressionLine(Smoth_SSMA13Arr,5)
        markLineCordSsma13 = getCoord(regressiveEqSmma13,Smoth_SSMA13Arr.length-1-5,Smoth_SSMA13Arr.length-1,5)

        console.log(markLineCordSsma5);

         var sma100Div = document.getElementById('sma100_trend');
         let sma100Trend = isUpTrend(regressiveEq[0],data,dataMA100,30,0.00001)
         nowUptrend = sma100Trend[1];
         MarkMA100Slope = sma100Trend[0];
        //  console.log(sma100Trend);

       if(nowUptrend == true){
            sma100Div.style.backgroundColor = '#00da3c';
       }else{
            sma100Div.style.backgroundColor = '#ffc107';
       }
    
         CrossClosedWithHistogram = crossListCalc(closedPrice,getSignChange(HistogramArr,MacdArr)); 
 
         var hisTogramData = getSignChange(HistogramArr,MacdArr);
        
         
         var previousSum = 0;
         if(numOfTick != 0){
            numOfTick ++;
            histogramIndex ++
            sumHistogram += HistogramArr[histogramIndex]
         }

         if(firstRun == true){
            numOfCross = hisTogramData.length;
            firstRun = false;
            // coordOfCrossSMA = crossSMA(dataMA5,dataMA10);
         }else{
             if(hisTogramData.length > numOfCross){
                // console.log(hisTogramData);
                 if(sumHistogram !== 0){
                    previousSum = sumHistogram;
                 }
                 if(MacdArr[histogramIndex] !== null){
                    macdAtCross.push(MacdArr[histogramIndex]);
                    // console.log(macdAtCross);
                 }
                 
                 if(onOrder == true){
                    console.log('?????????' + data[histogramIndex][1]);
                    Livewire.emit('getImage');
                    console.log('====');
                    onOrder = false;

                    MarkData.push({
                        name: "?????????",
                        value: data[histogramIndex][1],
                        xAxis: histogramIndex,
                        yAxis: data[histogramIndex][3],
                        color: "#000",
                    });
                 }

                

                numOfCross = hisTogramData.length;
                
                
                histogramIndex = hisTogramData[hisTogramData.length-1][0]


                histogramIndex ++;
         



                 if(HistogramData.length > 0){

                    const reducer = (accumulator, curr) => accumulator + curr;
                    var HistSum = HistogramData.reduce(reducer);
    
                    HistogramData = [];
                    MacdData = [];
                  

                 }

                 numOfTick = 1;


             }
         }
         if(numOfTick !== 0){
            if(data[histogramIndex] !== null){
                ClosedData.push(data[histogramIndex][1]);
                HistogramData.push(HistogramArr[histogramIndex]);
                MacdData.push(MacdArr[histogramIndex]);
                

            }
     
            var macdDiv = document.getElementById('macd_trend');
            if(hisTogramData[hisTogramData.length-1][3] !== ''){
                console.log('macd ???????????????????????????' + hisTogramData[hisTogramData.length-1][3])  ;
                
                if(hisTogramData[hisTogramData.length-1][3] == 'above'){
                    macdDiv.style.backgroundColor = '#00da3c';
                }else{
                    macdDiv.style.backgroundColor = '#ffc107';
                }
                
            }else{
                console.log('macd ????????????')  ;
                
                macdDiv.style.backgroundColor = '#ffc107';
            }
           

            var smaLong = dataMA50[dataMA50.length-1] ;
            var smaMedium = dataMA20[dataMA20.length-1] ;
            var smaShort = dataMA5[dataMA5.length-1] ;
            var smaDiffShortMedium = smaShort-smaMedium;
            var smaDiffMediumLong = smaMedium-smaLong;

            if(HistogramData.length >= 3 && hisTogramData[hisTogramData.length-1][3] == 'above'){
       

                if((smaDiffShortMedium > 0.02) && (smaDiffMediumLong > 0.02) && (smaDiffMediumLong/smaDiffShortMedium > 1) ){
                    
                    // console.log('smaDiffShortMedium:' + smaDiffShortMedium + ' smaDiffMediumLong:' + smaDiffMediumLong);
                    // console.log(HistogramData[2] + " "  + HistogramData[1] + " "  +  HistogramData[0]);
                    // console.log(smaDiffShortMedium/smaDiffMediumLong);
                    // console.log('========> trend ??????????????????');
                }
                // else{
                //     console.log('========> sideway ???????????? trend ????????????');
                // }
                
                if(onOrder === false){
                    if((HistogramData[2]/HistogramData[1] > 2 || HistogramData[3]/HistogramData[1] > 3) && MacdData[2] > 0.005 && hisTogramData[hisTogramData.length-1][2] > 0){
                        console.log('????????????????????????' + data[histogramIndex][1]);
                        MarkData.push({
                            name: "????????????",
                            value: data[histogramIndex][1],
                            xAxis: histogramIndex,
                            yAxis: data[histogramIndex][3]
                        });
                        onOrder = true;
                    }else if(((HistogramData[2] > HistogramData[1]) && (HistogramData[1] > HistogramData[0])) && ((smaDiffShortMedium > 0.02) && (smaDiffMediumLong > 0.02) && (smaDiffShortMedium/smaDiffMediumLong > 0.8)) ){
                        // console.log('smaDiffShortMedium:' + smaDiffShortMedium + ' smaDiffMediumLong:' + smaDiffMediumLong);
                        // console.log('????????????????????????????????? trend ?????????????????? ' + data[histogramIndex][1] + ' ????????????????????????????????? manual');
                        // MarkData.push({
                        //     name: "????????????",
                        //     value: data[histogramIndex][1],
                        //     xAxis: histogramIndex,
                        //     yAxis: data[histogramIndex][3],
                        //     color: "#fff",
                        // });
                        // onOrder = true;
                    }

                    // if(((HistogramData[2] > HistogramData[1]) && (HistogramData[1] > HistogramData[0])) && ((smaDiffShortMedium > 0.02) && (smaDiffMediumLong > 0.02) && (smaDiffShortMedium/smaDiffMediumLong > 0.8)) ){
                    //     console.log('SMA DiffShortMedium:' + smaDiffShortMedium + ' SMA DiffMediumLong:' + smaDiffMediumLong);
                    //     console.log('????????????????????????????????? trend ?????????????????? ' + data[histogramIndex][1] + ' ????????????????????????????????? manual');
                    //     MarkData.push({
                    //         name: '????????????',
                    //         value: data[histogramIndex][1],
                    //         xAxis: histogramIndex,
                    //         yAxis: data[histogramIndex][3]
                    //     });
                    //     onOrder = true;
                    // }
                } 
            }


            // if(onOrder == true){
            //     console.log('on order');
            //     if(smaDiffShortMedium < 0){
                    
            //         console.log('?????????' + data[histogramIndex][1]);
            //         Livewire.emit('getImage');
            //         MarkData.push({
            //             name: "?????????",
            //             value: data[histogramIndex][1],
            //             xAxis: histogramIndex,
            //             yAxis: data[histogramIndex][3],
            //             color: "#000",
            //         });
            //         console.log('====');
            //         onOrder = false;
            //     }
            // }
         }
         
        //  if(numOfTick > 30 && sumHistogram < -0.12 && MacdArr[histogramIndex] < -0.12){
        //     console.log('buy signal');
        //  }



    }
    // function saveImage(){
    //             var image = document.getElementById('captureCandle'); 
    //             var img = new Image();
    //             img.src = canndleStickChart.getDataURL({
    //                 pixelRatio: 2,
    //             });
    //             image.src = img.src;
    //         }
    document.addEventListener('livewire:load', () =>{
        Livewire.on('getImage', () => {
   
            var img_candlestick = new Image();
            img_candlestick.src = canndleStickChart.getDataURL({
                    pixelRatio: 2,
                });


      
            var img_macd = new Image();
            img_macd.src = macdChart.getDataURL({
                    pixelRatio: 2,
                });
      

            Livewire.emit('saveImage',img_candlestick.src,img_macd.src);
        })

        @this.on('refreshChart', (chartData) => {
            createData(chartData);
            canndleStickChart.setOption({
                series : [
                    {
                        name: 'USDJPY',
                        data: data,
                        markPoint: {
                            label: {
                                normal: {
                                    formatter: function (param) {
                                        return param != null ? param.data['name'] : '';
                                    }
                                }
                            },
                            data: MarkData,
                        },
                        markLine : {
                            data: [
                                [{
                                    coord: markLineCordSsma5[0],
                                    symbol : 'none',
                                    lineStyle: {
                                        color: '#91cc75'
                                    }
                                }, {
                                    coord: markLineCordSsma5[1],
                                    lineStyle: {
                                        color: '#91cc75'
                                    }
                                }],
                                [{
                                    coord: markLineCordSsma8[0],
                                    symbol : 'none',
                                    lineStyle: {
                                        color: '#fac858'
                                    }
                                }, {
                                    coord: markLineCordSsma8[1],
                                    lineStyle: {
                                        color: '#fac858'
                                    }
                                }],
                                [{
                                    coord: markLineCordSsma13[0],
                                    symbol : 'none',
                                    lineStyle: {
                                        color: '#ee6666'
                                    }
                                }, {
                                    coord: markLineCordSsma13[1],
                                    lineStyle: {
                                        color: '#ee6666'
                                    }
                                }]
                            ],
                        },
                    }, 
                    {
                        name: 'SSMA20',
                        data: Smoth_SSMA20Arr,
                    },
                    {
                        name: 'SSMA5',
                        data: Smoth_SSMA5Arr,
                    }, 
                    {
                        name: 'SSMA8',
                        data: Smoth_SSMA8Arr,
                    }, 
                    {
                        name: 'SSMA13',
                        data: Smoth_SSMA13Arr,
                    }
                    // 
                ],                
                xAxis : [
                    {
                        data : axisData
                    }
                ],
                dataZoom : {
                    start : Math.round((data.length-70)/(data.length)*100),
                    end : 100
                },

            });
            canndleStickChartZoom.setOption({
                series : [
                    {
                        name: 'USDJPYZOOM',
                        data: data,
                        markPoint: {
                            label: {
                                normal: {
                                    formatter: function (param) {
                                        return param != null ? param.data['name']['slope'] : '';
                                    }
                                }
                            },
                            symbol: 'circle',
                            symbolRotate: 0,
                            symbolSize: [1, 1],
                            itemStyle: {
                                color: 'black'
                            },
                            data: MarkMA100Slope,
                        },
                        markLine : {
                            data: [
                                [{
                                    coord: markMA100LineCord[0],
                                    symbol : 'none',
                                    lineStyle: {
                                        color: 'white'
                                    }
                                    }, {
                                    coord: markMA100LineCord[1],
                                    lineStyle: {
                                        color: 'white'
                                    }
                                }]
                            ],
                        },
                    }, 
                    {
                        name: 'MA100',
                        data: dataMA100,
                    }
                ],                
                xAxis : [
                    {
                        data : axisData
                    }
                ],
                dataZoom : {
                    start : Math.round((data.length-300)/(data.length)*100),
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
                    start : Math.round((data.length-70)/(data.length)*100),
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
                    start : Math.round((data.length-70)/(data.length)*100),
                    end : 100
                },
            });
            // cciChart.setOption({
            //     series : [
            //         {
            //             name:'CCI',
            //             data: CCIArr,
            //         }
            //     ],                
            //     xAxis : [
            //         {
            //             data : axisData
            //         }
            //     ],
            //     dataZoom : {
            //         start : Math.round((data.length-70)/(data.length)*100),
            //         end : 100
            //     },
            // });
        });
    });
    
    createData(forexData);
    

            option = {
                backgroundColor: '#21202D',
                title : {
                    text: 'USDJPY',
                    textStyle: {
                        color: '#fff'
                    }
                },
                tooltip : {
                    trigger: 'axis',
                    showDelay: 0,             // delay ms
                    // formatter: function (params) {
                    //     var res = params[0].name;
                    //     res += '<br/>' + params[0].seriesName;
                    //     res += '<br/>  Open : ' + params[0].value[1] + '  High : ' + params[0].value[4];
                    //     res += '<br/>  Close : ' + params[0].value[2] + '  Low : ' + params[0].value[3];
                    //     return res;
                    // }
                },
                legend: {
                    data:['USDJPY','SSMA20', 'SSMA5','SSMA8','SSMA13'],
                    selected:{'SSMA20':false},
                    textStyle: {
                        color: '#fff'
                    }
                },
                toolbox: {
                    show : true,
                    feature : {
                        // mark : {show: true},
                        // dataZoom : {show: true},
                        // magicType : {show: true, type: ['line', 'bar']},
                        // restore : {show: true},
                        saveAsImage : {show: true}
                    }
                },            
                dataZoom : {
                    y: 250,
                    show : false,
                    realtime: true,
                    start : Math.round((data.length-70)/(data.length)*100),
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
                        name:'USDJPY',

                        type:'candlestick',
                        data: data,
                        markLine : {
                            data: [
                                [{
                                    coord: markLineCordSsma5[0],
                                    symbol : 'none',
                                    
                                    lineStyle: {
                                        color: '#91cc75'
                                    }
                                    }, {
                                    coord: markLineCordSsma5[1],
                                    lineStyle: {
                                        color: '#91cc75'
                                    }
                                }],
                                [{
                                    coord: markLineCordSsma8[0],
                                    symbol : 'none',
                                    lineStyle: {
                                        color: '#fac858'
                                    }
                                }, {
                                    coord: markLineCordSsma8[1],
                                    lineStyle: {
                                        color: '#fac858'
                                    }
                                }],
                                [{
                                    coord: markLineCordSsma13[0],
                                    symbol : 'none',
                                    lineStyle: {
                                        color: '#ee6666'
                                    }
                                }, {
                                    coord: markLineCordSsma13[1],
                                    lineStyle: {
                                        color: '#ee6666'
                                    }
                                }]
                            ],
                        },
                        markPoint: {
                            data: [{
                                    symbol: 'circle',
                                    symbolSize: [5, 5],
                                    xAxis: 180,
                                    yAxis: 110,
                                    symbolRotate: 0,
                                    itemStyle: {
                                    color: 'black'
                                    },

                                }]
                            },
                        itemStyle: {
                            color: upColor,
                            color0: downColor,
                            borderColor: null,
                            borderColor0: null
                        },
                    }, 
  
                    {
                        name: 'SSMA20',
                        type: 'line',
                        data: Smoth_SSMA20Arr,
                        smooth: true,
                        showSymbol: false,
                        lineStyle: {
                            width: 1
                        }
                    }, {
                        name: 'SSMA5',
                        type: 'line',
                        data: Smoth_SSMA5Arr,
                        smooth: true,
                        showSymbol: false,
                        lineStyle: {
                            width: 1
                        }
                    }, {
                        name: 'SSMA8',
                        type: 'line',
                        data: Smoth_SSMA8Arr,
                        smooth: true,
                        showSymbol: false,
                        lineStyle: {
                            width: 1
                        }
                    }, {
                        name: 'SSMA13',
                        type: 'line',
                        data: Smoth_SSMA13Arr,
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

            option_zoom = {
                backgroundColor: '#21202D',
                title : {
                    text: 'USDJPY',
                    textStyle: {
                        color: '#fff'
                    }
                },
                tooltip : {
                    trigger: 'axis',
                    showDelay: 0,             // delay ms
                    // formatter: function (params) {
                    //     var res = params[0].name;
                    //     res += '<br/>' + params[0].seriesName;
                    //     res += '<br/>  Open : ' + params[0].value[1] + '  High : ' + params[0].value[4];
                    //     res += '<br/>  Close : ' + params[0].value[2] + '  Low : ' + params[0].value[3];
                    //     return res;
                    // }
                },
                legend: {
                    data:['USDJPYZOOM','MA100'],
                    textStyle: {
                        color: '#fff'
                    }
                },
                toolbox: {
                    show : true,
                    feature : {
                        // mark : {show: true},
                        // dataZoom : {show: true},
                        // magicType : {show: true, type: ['line', 'bar']},
                        // restore : {show: true},
                        saveAsImage : {show: true}
                    }
                },            
                dataZoom : {
                    y: 250,
                    show : false,
                    realtime: true,
                    start : Math.round((data.length-300)/(data.length)*100),
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
                        name:'USDJPYZOOM',
                        type:'candlestick',
                        data: data,
                        markPoint: {
                            label: {
                                normal: {
                                    formatter: function (param) {
                                        return param != null ? param.data['name']['slope'] : '';
                                    }
                                }
                            },
                            symbol: 'circle',
                            symbolRotate: 0,
                            symbolSize: [1, 1],
                            itemStyle: {
                                color: 'black'
                            },
                            data: MarkMA100Slope,
                        },
                        itemStyle: {
                            color: upColor,
                            color0: downColor,
                            borderColor: null,
                            borderColor0: null
                        },
                        markLine : {
                            data: [
                                [{
                                    coord: markMA100LineCord[0],
                                    symbol : 'none',
                                    lineStyle: {
                                        color: 'white'
                                    }
                                    }, {
                                    coord: markMA100LineCord[1],
                                    lineStyle: {
                                        color: 'white'
                                    }
                                }]
                            ],
                        },
                    }, 
                    {
                        name: 'MA100',
                        type: 'line',
                        data: dataMA100,
                        smooth: true,
                        showSymbol: false,
                        lineStyle: {
                            width: 1
                        }
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
                // toolbox: {
                //     y : -30,
                //     show : false,
                //     feature : {
                //         mark : {show: true},
                //         dataZoom : {show: true},
                //         dataView : {show: true, readOnly: false},
                //         magicType : {show: true, type: ['line', 'bar']},
                //         restore : {show: true},
                //         saveAsImage : {show: true}
                //     }
                // },
                dataZoom : 
                    {
                        show : true,
                        realtime: true,
                        start : Math.round((data.length-70)/(data.length)*100),
                        end : 100,
                        handleIcon: 'M10.7,11.9v-1.3H9.3v1.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4v1.3h1.3v-1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7V23h6.6V24.4z M13.3,19.6H6.7v-1.4h6.6V19.6z',
                        handleSize: '80%',
                        textStyle: {
                            color: '#fff'
                        },
                        handleStyle: {
                            color: '#fff',
                            shadowBlur: 3,
                            shadowColor: 'rgba(0, 0, 0, 0.6)',
                            shadowOffsetX: 2,
                            shadowOffsetY: 2
                        }
                    },
                // dataZoom: [
                    
                //         {
                //         textStyle: {
                //             color: '#8392A5'
                //         },
                        
                //         handleIcon: 'M10.7,11.9v-1.3H9.3v1.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4v1.3h1.3v-1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7V23h6.6V24.4z M13.3,19.6H6.7v-1.4h6.6V19.6z',
                //         handleSize: '80%',
                //         dataBackground: {
                //             areaStyle: {
                //                 color: '#8392A5'
                //             },
                //             lineStyle: {
                //                 opacity: 0.8,
                //                 color: '#8392A5'
                //             }
                //         },
                //         handleStyle: {
                //             color: '#fff',
                //             shadowBlur: 3,
                //             shadowColor: 'rgba(0, 0, 0, 0.6)',
                //             shadowOffsetX: 2,
                //             shadowOffsetY: 2
                //         }
                //     }, {
                //         type: 'inside'
                //     }],
                grid: {
                    x: 80,
                    y: 20,
                    x2: 20,
                    y2: 60
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
                    start : Math.round((data.length-70)/(data.length)*100),
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
                        //         return Math.round(v/10000) + ' ???'
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
                                }
                                ,{ 
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

            // option_CCI = {
            //     backgroundColor: '#21202D',
            //     tooltip : {
            //         trigger: 'axis',
            //         showDelay: 0             // delay ms
            //     },
            //     legend: {
            //         data:['CCI'],
            //         textStyle: {
            //             color: '#fff'
            //         }
            //     },
            //     toolbox: {
            //         y : -30,
            //         show : true,
            //         feature : {
            //             mark : {show: true},
            //             dataZoom : {show: true},
            //             dataView : {show: true, readOnly: false},
            //             magicType : {show: true, type: ['line', 'bar']},
            //             restore : {show: true},
            //             saveAsImage : {show: true}
            //         }
            //     },
            //     dataZoom : {
            //         bottom: 5,
            //         show : true,
            //         realtime: true,
            //         start : Math.round((data.length-70)/(data.length)*100),
            //         end : 100
            //     },
            //     grid: {
            //         x: 80,
            //         y: 20,
            //         x2: 20,
            //         y2: 80
            //     },
            //     xAxis : [
            //         {
            //             show : false,
            //             type : 'category',
            //             position:'top',
            //             // boundaryGap : true,
            //             splitLine: {show:false},
            //             data : axisData
            //         }
            //     ],
            //     yAxis : [
            //         {
            //             type : 'value',
            //             scale:true,
            //             splitNumber: 3,
            //             boundaryGap: [0.05, 0.05],
            //             splitArea : {show : true}
            //         }
            //     ],
            //     series : [
            //         {
            //             name:'CCI',
            //             type:'line',
            //             symbol: 'none',
            //             data: CCIArr,
            //             markLine : {
            //                 symbol : 'none',
            //                 itemStyle : {
            //                     normal : {
            //                         color:'#1e90ff',
            //                         label : {
            //                             show:true
            //                         }
            //                     }
            //                 },
            //                 data : [
            //                     { 
            //                         name: 'Hi', 
            //                         yAxis: 100,
            //                         lineStyle: {
            //                             color: '#14b143'
            //                         },
            //                     },{ 
            //                         name: 'Low', 
            //                         yAxis: -100,
            //                         lineStyle: {
            //                             color: '#ef232a'
            //                         },
            //                     }
            //                 ]
            //             }
            //         }
            //     ]
            // };

            var canndleStickChart = echarts.init(document.getElementById('canndle_stick_chart'));  
            var canndleStickChartZoom = echarts.init(document.getElementById('canndle_stick_chart_zoom')); 
            var macdChart = echarts.init(document.getElementById('macd_chart')); 
            // var cciChart = echarts.init(document.getElementById('cci_chart'));
            var rsiChart = echarts.init(document.getElementById('rsi_chart'));

            canndleStickChart.setOption(option);
            macdChart.setOption(option_macd);
            canndleStickChartZoom.setOption(option_zoom);
            // cciChart.setOption(option_CCI);
            rsiChart.setOption(option_rsi);

            echarts.connect([canndleStickChart, macdChart,canndleStickChartZoom,rsiChart]);

            window.onresize = function () {
                canndleStickChart.resize();
                macdChart.resize();
                canndleStickChartZoom.resize();
                // cciChart.resize();
                // rsiChart.resize();
            }


        function play() { 
            var dingdong = new Audio( 
            'http://localhost/dingdong/dingdong.mp3'); 
            dingdong.play(); 
        } 

 
    </script>
@endpush
