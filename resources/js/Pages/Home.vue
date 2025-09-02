<script setup>
import {route} from "ziggy-js";
import {router} from "@inertiajs/vue3";
</script>

<template>
    <h1>Home page</h1>
    <v-btn color="primary" @click="router.visit(route('dashboard'))">Dashboard</v-btn>
</template>

<style scoped>

</style>
