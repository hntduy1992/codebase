<script setup>
import {computed, ref} from 'vue'
import {route} from 'ziggy-js'
import {router, usePage} from "@inertiajs/vue3";

const drawer = ref(true)

const links = [
    {text: 'Dashboard', icon: 'mdi-view-dashboard', route_name: 'home'},
    {text: 'Users', icon: 'mdi-account', route_name: 'users.index'},
]
const page = usePage()
const isAuthenticated = computed(() => page.props.auth.user);
</script>

<template>
    <v-app id="inspire">
        <v-navigation-drawer v-model="drawer" v-if="isAuthenticated">
            <v-sheet
                class="pa-4"
                color="grey-lighten-4"
            >
                <v-avatar
                    class="mb-4"
                    color="grey-darken-1"
                    size="64"
                ></v-avatar>

                <div>john@google.com</div>
            </v-sheet>

            <v-divider></v-divider>

            <v-list>
                <v-list-item
                    v-for="link in links"
                    :key="link.icon"
                    :prepend-icon="link.icon"
                    :title="link.text"
                    link
                    @click="router.visit(route(link.route_name))"
                ></v-list-item>
            </v-list>
        </v-navigation-drawer>

        <v-main>
            <v-app-bar v-if="isAuthenticated">
                <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>

                <v-app-bar-title>Application</v-app-bar-title>
            </v-app-bar>
            <slot></slot>
        </v-main>
    </v-app>
</template>

<style scoped>

</style>
