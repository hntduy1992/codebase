import {useForm} from "@inertiajs/vue3";
import {route} from 'ziggy-js'

const userService = {
    form: useForm({
        username: '',
        password: ''
    }),
    create: (callback) => {
        this.form.post(route('users.store'))
        console.log('users.create')
    }
}
export default userService
