import {useForm} from "@inertiajs/vue3";

const authService = {
    loginForm: useForm({
        username: '',
        password: ''
    }),
}
export default authService
