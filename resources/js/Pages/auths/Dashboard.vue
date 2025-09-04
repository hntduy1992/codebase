<script setup>
import {route} from "ziggy-js";
import {ref} from "vue";

const list = ref([])
const apiCall = async () => {
    console.log('call')
    await axios.get(route('api.test')).then((res) => {
        list.value = res.data.data;
    })
}


</script>

<template>
    <v-btn @click="apiCall">call api</v-btn>
    <pre>{{ list }}</pre>
</template>

<style scoped>

</style>
