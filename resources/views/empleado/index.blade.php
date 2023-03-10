@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <h1>USUARIOS</h1>
@stop

@section('content')
<div class="card" >
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
              <thead>
                <tr>
                  <th scope="col">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                    </div>
                  </th>
                  <th scope="col">EMPLOYEES</th>
                  <th scope="col">POSITION</th>
                  <th scope="col">AGE</th>
                  <th scope="col">ADDRESS</th>
                  <th scope="col">Close</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <th scope="row">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault1" checked/>
                    </div>
                  </th>
                  <td>Tiger Nixon</td>
                  <td>System Architect</td>
                  <td>61</td>
                  <td>Edinburgh</td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm px-3">
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
                <tr>
                  <th scope="row">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault2" />
                    </div>
                  </th>
                  <td>Sonya Frost</td>
                  <td>Software Engineer</td>
                  <td>23</td>
                  <td>Edinburgh</td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm px-3">
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
                <tr>
                  <th scope="row">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault3" checked/>
                    </div>
                  </th>
                  <td>Jena Gaines</td>
                  <td>Office Manager</td>
                  <td>30</td>
                  <td>London</td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm px-3">
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
                <tr>
                  <th scope="row">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault4" checked/>
                    </div>
                  </th>
                  <td>Quinn Flynn</td>
                  <td>Support Lead</td>
                  <td>22</td>
                  <td>Edinburgh</td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm px-3">
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
                <tr>
                  <th scope="row">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault5" checked/>
                    </div>
                  </th>
                  <td>Charde Marshall</td>
                  <td>Regional Director</td>
                  <td>36</td>
                  <td>San Francisco</td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm px-3">
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
                <tr>
                  <th scope="row">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault6" />
                    </div>
                  </th>
                  <td>Haley Kennedy</td>
                  <td>Senior Marketing Designer</td>
                  <td>43</td>
                  <td>London</td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm px-3">
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
                <tr>
                  <th scope="row">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault7" checked/>
                    </div>
                  </th>
                  <td>Tatyana Fitzpatrick</td>
                  <td>Regional Director</td>
                  <td>19</td>
                  <td>Warsaw</td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm px-3">
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
    </div>
</div>
@stop


@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

