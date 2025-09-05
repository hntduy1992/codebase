<script setup>
import {computed, ref} from 'vue'
import {route} from 'ziggy-js'
import {router, usePage} from "@inertiajs/vue3";
import * as url from "url";

const drawer = ref(true)

const links = [
    {text: 'Dashboard', icon: 'mdi-view-dashboard', route_name: 'home'},
    {text: 'Users', icon: 'mdi-account', route_name: 'users.index'},
]
const page = usePage()
const isAuthenticated = computed(() => page.props.auth.user);

const onLogin = () => {
    router.visit(route('login', {'callback': document.URL}))
}
</script>

<template>
    <v-app id="inspire">
        <v-navigation-drawer v-model="drawer" v-if="isAuthenticated">
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

                <v-spacer/>
                <v-menu
                    open-on-hover>
                    <template v-slot:activator="{ props }">
                        {{ page.props.auth.user.ho_ten }}
                        <v-avatar color="surface-variant"
                                  image="https://sohanews.sohacdn.com/2019/9/27/photo-1-1569551899490126660409.jpg"
                                  v-bind="props"
                                  class="ma-3"
                        >
                            <span v-if="!page.props.auth.user.avatar">
                                {{ page.props.auth.user.ho_ten.slice(0, 2) }}
                            </span>
                        </v-avatar>
                    </template>
                    <v-list>
                        <v-list-item link @click="router.visit(route('dashboard'))">
                            <template v-slot:prepend>
                                <v-icon>mdi-dashboard</v-icon>
                            </template>
                            <v-list-item-title>Dashboard</v-list-item-title>
                        </v-list-item>
                        <v-list-item link>
                            <template v-slot:prepend>
                                <v-icon>mdi-card-account-details-outline</v-icon>
                            </template>
                            <v-list-item-title>Profile</v-list-item-title>
                        </v-list-item>
                        <v-list-item link>
                            <template v-slot:prepend>
                                <v-icon>mdi-form-textbox-password</v-icon>
                            </template>
                            <v-list-item-title>Change password</v-list-item-title>
                        </v-list-item>
                        <v-divider/>
                        <v-list-item link @click="logout">
                            <template v-slot:prepend>
                                <v-icon>mdi-logout</v-icon>
                            </template>
                            <v-list-item-title>Logout</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-menu>
            </v-app-bar>
            <v-app-bar v-else>
                <v-app-bar-title>Application</v-app-bar-title>
                <v-list class="d-flex">
                    <v-list-item link @click="router.visit(route('tinTuc'))">
                        <v-list-item-title>TIN TỨC</v-list-item-title>
                    </v-list-item>
                    <v-list-item link href="#su-kien">
                        <v-list-item-title>SỰ KIỆN</v-list-item-title>
                    </v-list-item>
                    <v-list-item link href="#the-thao">
                        <v-list-item-title>THỂ THAO</v-list-item-title>
                    </v-list-item>
                    <v-list-item link href="#du-lich">
                        <v-list-item-title>DU LỊCH</v-list-item-title>
                    </v-list-item>
                </v-list>
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="onLogin">Login</v-btn>
            </v-app-bar>
            <slot></slot>
        </v-main>
    </v-app>
</template>

<style scoped>

</style>
