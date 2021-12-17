try {
    window.$ = window.jQuery = require('jquery');
    require('admin-lte');
    require('admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js');
    window.toastr = require('admin-lte/plugins/toastr/toastr.min.js');
    window.Swal = require('sweetalert2/dist/sweetalert2.js');
    window.echarts = require('echarts/dist/echarts.js');
    // require('./stock.js');
} catch (e) {}
