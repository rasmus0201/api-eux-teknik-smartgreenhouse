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
                <input class="form-control" type="number" min="1" v-model="delta" placeholder="Time between datapoints">
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
                <div class="custom-control custom-checkbox">
                    <input v-model="autorefresh.active" type="checkbox" class="custom-control-input" id="autorefresh-active">
                    <label class="custom-control-label" for="autorefresh-active">Autorefresh</label>
                </div>
            </div>
        </div>
        <div v-if="autorefresh.active" class="row my-3">
            <div class="col-3">
                <input class="form-control" type="number" min="1" v-model="autorefresh.interval" placeholder="Time between refresh (seconds)">
            </div>
            <div class="col-3">
                <div class="custom-control custom-checkbox">
                    <input v-model="autorefresh.movingEnd" type="checkbox" class="custom-control-input" id="autorefresh-movingEnd">
                    <label class="custom-control-label" for="autorefresh-movingEnd">Should the end date move?</label>
                </div>
            </div>
            <div v-if="autorefresh.movingEnd" class="col-2">
                <input class="form-control" type="number" min="1" v-model="autorefresh.delta" placeholder="How much to add?">
            </div>
            <div v-if="autorefresh.movingEnd" class="col-2">
                <select class="form-control" v-model="autorefresh.movingInterval">
                    <option v-for="(val, index) in autorefresh.intervals" :value="val.multiplier" :key="index" :selected="autorefresh.movingInterval == val.multiplier">
                        {{ val.label }}
                    </option>
                </select>
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
            autorefresh: {
                active: false,
                movingEnd: false,
                interval: 30,
                delta: 1,
                movingInterval: 60,
                timer: null,
                intervals: [
                    {
                        multiplier: 1,
                        label: 'Sec'
                    },
                    {
                        multiplier: 60,
                        label: 'Min'
                    },
                    {
                        multiplier: 60 * 60,
                        label: 'Hour'
                    },
                    {
                        multiplier: 60 * 60 * 24,
                        label: 'Day'
                    },
                ],
            },
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
                temperature: '#ff0000',
                humidity: '#00ff00',
                pressure: '#0000ff',
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
        },

        autorefreshDeltaSeconds() {
            const delta = this.autorefresh.delta * this.autorefresh.movingInterval;

            return delta >= 1 ? delta : 1;
        },
    },
    watch: {
        'autorefresh.active' (newVal) {
            if (newVal === true) {
                this.autorefresh.timer = setInterval(this.intervalData, this.autorefresh.interval * 1000);
            } else {
                clearInterval(this.autorefresh.timer);
            }
        },

        'autorefresh.interval' (newVal) {
            if (newVal <= 0) {
                return;
            }

            if (this.autorefresh.active === false) {
                return;
            }

            if (this.autorefresh.timer) {
                clearInterval(this.autorefresh.timer);
            }

            this.autorefresh.timer = setInterval(this.intervalData, this.autorefresh.interval * 1000);
        },

    },
    mounted() {
        this.getData();
    },
    methods: {
        intervalData() {
            if (this.autorefresh.movingEnd === true) {
                this.endDate = DateTime.fromISO(this.endDate)
                    .plus({ seconds: this.autorefreshDeltaSeconds })
                    .toISO();
            }

            this.getData();
        },

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
