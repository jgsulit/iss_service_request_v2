@extends('layouts.admin_layout')

@section('title', 'Service Types')

@section('content_page')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1><i class="fas fa-service_types"></i> Service Types</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Service Types</li>
          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <!-- left column -->
        <div class="col-md-12">
          <!-- general form elements -->
          <div class="card card-primary">
            <!-- Start Page Content -->
            <div class="card-body">
              <div class="row">
                <div class="col-sm-12">
                  @php
                    $display = 'block';
                  @endphp
                  <div class="float-sm-left" style="min-width: 200px; display: {{ $display }}">
                    <div class="form-group row">
                      <div class="col">
                        <div class="input-group input-group-sm mb-3">
                          <div class="input-group-prepend w-20">
                            <span class="input-group-text w-100">Status</span>
                          </div>
                          <select class="form-control form-control-sm selFilByStat" name="status">
                            <option value="1" selected="true">Active</option>
                            <option value="2">Archived</option>
                          </select>
                        </div>
                      </div>
                    </div> <!-- .form-group row -->
                  </div> <!-- .float-sm-left -->

                  <div class="float-sm-right">
                    <button class="btn btn-primary btn-sm btnAddServiceType"><i class="fa fa-plus"></i> Add New</button>
                  </div> <!-- .float-sm-right -->
                  <br><br>

                  <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover" id="tblServiceTypes" style="width: 100%;">
                      <thead>
                        <tr>
                          <th>Task Type</th>
                          <th>Description</th>
                          <th>Suggested TRT</th>
                          <th>Status</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        
                      </tbody>
                    </table>
                  </div> <!-- .table-responsive -->
                </div> <!-- .col-sm-12 -->
              </div> <!-- .row -->
            </div>
            <!-- !-- End Page Content -->

          </div>
          <!-- /.card -->
        </div>
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- MODALS -->
<div class="modal fade" id="mdlSaveServiceType">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><i class="fa fa-info-circle text-info"></i> Service Type Details</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form class="form-horizontal" id="frmSaveServiceType">
        @csrf
        <div class="modal-body">
          <div class="card-body">
            <div class="form-group row">
              <label class="col-sm-4 col-form-label">Task Type</label>
              <div class="col-sm-8">
                <select class="form-control" name="task_type">
                  <option value="1" selected="true">Software Task</option>
                  <option value="2">Hardware Task</option>
                </select>
                <span class="text-danger float-sm-right input-error"></span>
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-4 col-form-label">Description</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" name="service_type_id" placeholder="ServiceType ID" style="display: none;">
                <input type="text" class="form-control" name="description" placeholder="Description">
                <span class="text-danger float-sm-right input-error"></span>
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-4 col-form-label">Suggested TRT</label>
              <div class="col-sm-8">
                <select class="form-control" name="suggested_trt">
                  <option value="0" selected="true">N/A</option>
                  <option value="0.4">E4</option>
                  <option value="1">R1</option>
                  <option value="2">R2</option>
                  <option value="3">R3</option>
                  <option value="4">R4</option>
                  <option value="5">R5</option>
                  <option value="-1">RX</option>
                </select>
                <span class="text-danger float-sm-right input-error"></span>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary btnSaveServiceType">Save</button>
        </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<script type="text/javascript">
  // Variables
  let dtServiceTypes, frmSaveServiceType, btnSaveServiceType;
</script>

@endsection

@section('js_content')
<!-- Custom Links -->
<script src="{{ asset('public/scripts/client/ServiceType.js') }}"></script>

<!-- JS Codes -->
<script type="text/javascript">
  $(document).ready(function () {
    frmSaveServiceType = $("#frmSaveServiceType");
    btnSaveServiceType = $('.btnSaveServiceType');

    bsCustomFileInput.init();
    //Initialize Select2 Elements
    $('.select2').select2();

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    });
    
    toastr.options = {
      "closeButton": false,
      "debug": false,
      "newestOnTop": true,
      "progressBar": true,
      "positionClass": "toast-top-right",
      "preventDuplicates": false,
      "onclick": null,
      "showDuration": "300",
      "hideDuration": "3000",
      "timeOut": "3000",
      "extendedTimeOut": "3000",
      "showEasing": "swing",
      "hideEasing": "linear",
      "showMethod": "fadeIn",
      "hideMethod": "fadeOut",
    };

    $(document).on('click','#tblServiceTypes tbody tr',function(e){
      $(this).closest('tbody').find('tr').removeClass('table-active');
      $(this).closest('tr').addClass('table-active');
    });

    $.fn.dataTable.ext.errMode = 'none';

    dtServiceTypes = $("#tblServiceTypes").DataTable({
      "processing" : false,
      "serverSide" : true,
      "ajax" : {
        url: "{{ route('view_service_types') }}",
        data: function (param){
            param.status = $(".selFilByStat").val();
        }
      },
      
      "columns":[
        { "data" : "raw_task_type" },
        { "data" : "description" },
        { "data" : "raw_suggested_trt" },
        { "data" : "raw_status" },
        { "data" : "raw_action", orderable:false, searchable:false }
      ],

      "columnDefs": [ 
        {
          "targets": [0, 1, 2],
          "data": null,
          "defaultContent": "--"
        },
        // { "visible": false, "targets": 1 }
      ],
      "order": [[ 1, "asc" ]],
      "initComplete": function(settings, json) {
          
      },
      "drawCallback": function( settings ) {
          
      }
    }).on( 'error', function () {
      toastr.warning('DataTable not loaded properly. Please reload the page. <br> <button class="pull-right btn btn-danger btn-xs btnReload float-sm-right">Reload</button>');
    });//end of dtServiceTypes

    $(document).on('click', '.btnReload', function(){
      // window.location.reload();
      dtServiceTypes.draw();
    });

    $(".selFilByStat").change(function(e){
      dtServiceTypes.draw();
    });

    $(".btnAddServiceType").click(function(e){
      $("#mdlSaveServiceType").modal('show');
      frmSaveServiceType[0].reset();
      $(".input-error", frmSaveServiceType).text('');
      $(".form-control", frmSaveServiceType).removeClass('is-invalid');
    });

    $('#mdlSaveServiceType').on('shown.bs.modal', function (e) {
      $('input[name="description"]', frmSaveServiceType).focus();
    })

    $("#tblServiceTypes").on('click', '.btnActions', function(e){
      let serviceTypeId = $(this).attr('service-type-id');
      let action = $(this).attr('action');
      let status = $(this).attr('status');
      let title = '';

      if(action == 1){
        if(status == 2){
          title = 'Archive Service Type';        
        }
        else if(status == 1){
          title = 'Restore Service Type';        
        }
      }
      // else if(action == 2){
      //   title = 'Reset Password';
      // }

      $.confirm({
        title: title,
        content: 'Please confirm to continue.',
        backgroundDismiss: true,
        type: 'blue',
        buttons: {
          confirm: {
            text: 'Confirm',
            btnClass: 'btn-blue',
            keys: ['enter'],
            action: function(){
              ServiceTypeAction(serviceTypeId, action, status);
              cnfrmLoading.open();
            }
          },
          cancel: function () {
            
          },
        }
      });
    });

    $("#frmSaveServiceType").submit(function(e){
      e.preventDefault();
      SaveServiceType();
    });

    $("#tblServiceTypes").on('click', '.btnEditServiceType', function(e){
      let serviceTypeId = $(this).attr('service-type-id');
      GetServiceTypeById(serviceTypeId);
    });

  });
</script>
@endsection