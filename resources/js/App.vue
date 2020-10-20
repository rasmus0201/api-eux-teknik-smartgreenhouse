<template>
    <div class="container-fluid py-3">
        <div class="row">
            <div class="col-3">
                <datetime v-model="startDate" type="datetime" :format="dt.DATETIME_MED_WITH_SECONDS"></datetime>
            </div>
            <div class="col-3">
                <datetime v-model="endDate" type="datetime" :format="dt.DATETIME_MED_WITH_SECONDS"></datetime>
            </div>
            <div class="col-2">
                <input class="form-control" type="number" v-model="delta" placeholder="Time between datapoints">
            </div>
            <div class="col-2">
                <select class="form-control" v-model="interval">
                    <option v-for="(val, index) in intervals" :value="val.multiplier" :key="index" :selected="interval == val.multiplier">
                        {{ val.label }}
                    </option>
                </select>
            </div>
            <div class="col-2">
                <button @click="getData" class="btn btn-primary">Refresh</button>
            </div>
        </div>
        <LineGraph class="mt-3" :chart-data="chartData" :options="chartOptions" />
    </div>
</template>

<script>
import { DateTime } from "luxon";
import LineGraph from './Components/LineGraph.vue';

export default {
    name: "App",
    components: {
        LineGraph
    },
    data() {
        return {
            delta: 1,
            interval: 60,
            startDate: DateTime.local().startOf('day').toISO(),
            endDate: DateTime.local().endOf('day').toISO(),
            chartData: {},
            chartOptions: {
                responsive: true,
                maintainAspectRatio: false
            },
            intervals: [
                {
                    multiplier: 1,
                    label: 'Min'
                },
                {
                    multiplier: 60,
                    label: 'Hour'
                },
                {
                    multiplier: 60 * 24,
                    label: 'Day'
                },
            ],
            chartColors: {
                humidity: '#ff0000',
                light: '#00ff00',
                sound: '#0000ff',
                temperature: '#ffff00',
                vibration: '#ff00ff',
                secondary: '#00ffff',
            }
        };
    },
    computed: {
        dt() {
            return DateTime;
        },
        dateInterval() {
            let s = DateTime.fromISO(this.startDate);
            let e = DateTime.fromISO(this.endDate);

            return 'start='+s.toSeconds()+"&end="+e.toSeconds();
        },
        deltaSeconds() {
            return this.delta * this.interval;
        }
    },
    mounted() {
        this.getData();
    },
    methods: {
        getData() {
            fetch('api/v1/graph?delta='+this.deltaSeconds+'&'+this.dateInterval)
                .then((res) => res.json())
                .then((result) => {
                    const data = result.data;

                    const labels = [];
                    const pivot = {};
                    for (const key in data) {
                        if (data.hasOwnProperty(key)) {
                            const dataPoints = data[key];

                            const d = DateTime.fromSeconds(parseInt(key, 10));
                            labels.push(d.toLocaleString(DateTime.DATETIME_SHORT_WITH_SECONDS));

                            for (const dataPoint of dataPoints) {
                                if (typeof pivot[dataPoint.sensor] === "undefined") {
                                    pivot[dataPoint.sensor] = {
                                        fill: false,
                                        label: dataPoint.sensor,
                                        borderColor: this.chartColors[dataPoint.sensor],
                                        data: []
                                    };
                                }

                                pivot[dataPoint.sensor].data.push(dataPoint.avg);
                            }
                        }
                    }

                    const datasets = [];
                    for (const key in pivot) {
                        if (pivot.hasOwnProperty(key)) {
                            const dataset = pivot[key];
                            while (dataset.data.length < labels.length) {
                                dataset.data.push(0);
                            }

                            datasets.push(dataset);
                        }
                    }

                    this.chartData = {
                        labels,
                        datasets
                    };
                });
        }
    }
};
</script>

<style lang="scss">
    .vdatetime-input {
        display: block;
        width: 100%;
        height: calc(1.5em + .75rem + 2px);
        padding: .375rem .75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        border-radius: .25rem;
        transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    }
</style>