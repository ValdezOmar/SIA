<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laradevsbd\Zkteco\Http\Library\ZktecoLib;
use App\Models\RRHH\Asistencia;


class DescargarAsistencias extends Command
{
    protected $signature = 'zkteco:descargar-asistencias';
    protected $description = 'Conectar al equipo ZKTeco y guardar asistencias en la base de datos';

    public function handle()
    {
        $zk = new ZktecoLib(config('zkteco.ip'), config('zkteco.port'));

        $this->info("Conectando a ZKTeco en " . config('zkteco.ip'));

        if ($zk->connect()) {
            $this->info("Conexión exitosa.");

            $asistencias = $zk->getAttendance();

            if (!empty($asistencias)) {
                foreach ($asistencias as $registro) {
                    Asistencia::updateOrCreate(
                        [
                            'uid' => $registro['uid'],
                            'user_id' => $registro['id'],
                            'fecha_hora' => $registro['timestamp'],
                        ],
                        [
                            'estado' => $registro['state'],
                            'tipo' => $registro['type'],
                        ]
                    );
                }

                $this->info("Se guardaron " . count($asistencias) . " registros.");
            } else {
                $this->warn("No se encontraron asistencias en el dispositivo.");
            }

            $zk->disconnect();
        } else {
            $this->error("No se pudo conectar con el equipo.");
        }
    }
}
