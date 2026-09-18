<script>
    window.filamentChartJsPlugins ??= [];

    if (! window.filamentChartJsPlugins.some((plugin) => plugin.id === 'siaValueLabels')) {
        window.filamentChartJsPlugins.push({
            id: 'siaValueLabels',

            afterDatasetsDraw(chart, _args, options) {
                if (! options?.enabled || chart.config.type !== 'bar') return;

                const { ctx } = chart;
                const currency = options.currency ?? 'Bs';

                ctx.save();
                ctx.font = '600 11px Nunito Sans, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.fillStyle = getComputedStyle(chart.canvas).color;

                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    chart.getDatasetMeta(datasetIndex).data.forEach((bar, index) => {
                        const value = Number(dataset.data[index] ?? 0);

                        if (! Number.isFinite(value) || value <= 0) return;

                        ctx.fillText(`${currency} ${value.toLocaleString('es-BO', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        })}`, bar.x, bar.y - 6);
                    });
                });

                ctx.restore();
            },
        });
    }
</script>
