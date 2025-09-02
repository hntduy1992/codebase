<script setup>
import {ref} from 'vue';
import {useForm} from "@inertiajs/vue3";
import logo from '../../../../images/logo-sadec.png'
import {route} from "ziggy-js";

const showPassword = ref(false);
const loginForm = useForm({
    username: '',
    password: ''
})
const formSubmit = async () => {
  await loginForm.post(route('checkLogin'));
};
</script>

<template>
    <v-container fluid class="heigh-full d-flex align-center justify-center bg-grey-lighten-2">
        <v-card width="600">
            <v-card-title class="text-center">
                <h2 class="text-center">QUẢN TRỊ HỆ THỐNG</h2>
            </v-card-title>
            <v-card-text>
                <v-row>
                    <v-col cols="6">
                        <v-img :src="logo" class="logo mx-auto" width="250"></v-img>
                    </v-col>
                    <v-col cols="6">
                        <h2 class="text-center mb-3">Đăng nhập</h2>
                        <v-form @submit.prevent="formSubmit">
                            <v-text-field
                                v-model="loginForm.username"
                                label="Tên người dùng"
                                prepend-inner-icon="mdi-account"
                                variant="outlined"
                                required
                                class="mb-4"
                                :error-messages="loginForm.errors.username"
                            ></v-text-field>

                            <v-text-field
                                v-model="loginForm.password"
                                label="Mật khẩu"
                                prepend-inner-icon="mdi-lock"
                                :type="showPassword ? 'text' : 'password'"
                                :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                @click:append-inner="showPassword = !showPassword"
                                variant="outlined"
                                required
                                class="mb-4"
                                :error-messages="loginForm.errors.password"
                            ></v-text-field>

                            <v-btn
                                color="primary"
                                block
                                large
                                type="submit"
                            >
                                Đăng nhập
                            </v-btn>
                        </v-form>
                    </v-col>
                </v-row>


            </v-card-text>
        </v-card>

    </v-container>
</template>

<style scoped>
.wrapper {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
}

.heigh-full {
    height: 100vh;
}

.logo {

}
</style>
