<!-- Main Sidebar Container - auto expand para abrir en hover-->
<aside class="main-sidebar border-0 sidebar-light-primary elevation-4 sidebar-no-expand">
    <!-- Brand Logo -->    
    <a href="{{ url('/') }}" class="brand-link logo-switch border-bottom-0">
    <img src="../storage/img/logo_simple.png" alt="UNIR" class="brand-image-xl logo-xs">
    <img src="../storage/img/logo_largo.png" alt="UNIR" class="brand-image-xs logo-xl" style="left: 12px">
    </a>

    <!-- Sidebar -->
    <div class="sidebar">

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent " data-widget="treeview"  data-accordion="true" data-expandSidebar="true" role="menu" >

                @auth
                <li class="nav-item ">
                    <a href="{{ route('home') }}" class="nav-link" title="gtuytutiuy">
                        <i class="nav-icon icon ion-md-search"></i>
                        <p>
                            Seguimiento de Tramite
                        </p>
                    </a>
                </li>

                <li class="nav-item menu-open menu-is-opening">
                    <a href="#" class="nav-link ">
                        <i class="nav-icon icon ion-md-attach"></i>
                        <p>
                            Correspondencia
                            <i class="nav-icon right icon ion-md-arrow-back"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview ">                                                 
                           
                            @can('view-any', App\Models\CiteInterno::class)
                            <li class="nav-item">
                                <a href="{{ route('cite-internos.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-mail"></i>
                                    <p>Bandeja Entrada</p>
                                </a>
                            </li>
                            @endcan
                            @can('view-any', App\Models\CiteInterno::class)
                            <li class="nav-item">
                                <a href="{{ route('cite-internos.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-mail-open"></i>
                                    <p>Bandeja Pendientes</p>
                                </a>
                            </li>
                            @endcan
                            
                            @can('view-any', App\Models\CiteInterno::class)
                            <li class="nav-item">
                                <a href="{{ route('cite-internos.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-paper-plane"></i>
                                    <p>Bandeja Salida</p>
                                </a>
                            </li>
                            @endcan
                            @can('view-any', App\Models\CiteInterno::class)
                            <li class="nav-item">
                                <a href="{{ route('cite-internos.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-radio-button-off"></i>
                                    <p>Cite</p>
                                </a>
                            </li>
                            @endcan
                            @can('view-any', App\Models\Derivacion::class)
                            <li class="nav-item">
                                <a href="{{ route('derivaciones.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-radio-button-off"></i>
                                    <p>Derivaciones</p>
                                </a>
                            </li>
                            @endcan                           
                            @can('view-any', App\Models\Tramite::class)
                            <li class="nav-item">
                                <a href="{{ route('tramites.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-radio-button-off"></i>
                                    <p>Tramites</p>
                                </a>
                            </li>
                            @endcan
                            @can('view-any', App\Models\RemitenteExterno::class)
                            <li class="nav-item">
                                <a href="{{ route('remitente-externos.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-radio-button-off"></i>
                                    <p>Remitente Externo</p>
                                </a>
                            </li>
                            @endcan
                            @can('view-any', App\Models\TipoDocumento::class)
                            <li class="nav-item">
                                <a href="{{ route('tipo-documentos.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-radio-button-off"></i>
                                    <p>Tipo Documentos</p>
                                </a>
                            </li>
                            @endcan
                            
                    </ul>
                </li>

                 <!-- Modulo de recursos humanos -->
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon icon ion-md-people"></i>
                        <p>
                            Recursos Humanos
                            <i class="nav-icon right icon ion-md-arrow-round-back"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                            @can('view-any', App\Models\FilePersonal::class)
                            <li class="nav-item">
                                <a href="{{ route('file-personal.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-document"></i>
                                    <p>File Personal</p>
                                </a>
                            </li>
                            @endcan
                            @can('view-any', App\Models\User::class)
                            <li class="nav-item">
                                <a href="{{ route('users.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-person-add"></i>
                                    <p>Empleados</p>
                                </a>
                            </li>
                            @endcan
                            @can('view-any', App\Models\Cargo::class)
                            <li class="nav-item">
                                <a href="{{ route('cargos.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-list"></i>
                                    <p>Cargos</p>
                                </a>
                            </li>
                            @endcan
                            @can('view-any', App\Models\Area::class)
                            <li class="nav-item">
                                <a href="{{ route('areas.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-git-merge"></i>
                                    <p>Areas</p>
                                </a>
                            </li>
                            @endcan
                            
                            @can('view-any', App\Models\Permisos::class)
                            <li class="nav-item">
                                <a href="{{ route('permisos.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-walk"></i>
                                    <p>Permisos</p>
                                </a>
                            </li>
                            @endcan                           
                            @can('view-any', App\Models\TipoPermiso::class)
                            <li class="nav-item">
                                <a href="{{ route('tipo-permisos.index') }}" class="nav-link">
                                    <i class="nav-icon icon ion-md-swap"></i>
                                    <p>Tipo Permisos</p>
                                </a>
                            </li>
                            @endcan
                    </ul>
                </li>

                @endauth               
                @auth
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="nav-icon icon ion-md-exit"></i>
                        <p>{{ __('Logout') }}</p>
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
                @endauth
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>