<script setup lang="ts">
import variables from '../../../sass/variables.module.scss'
import { usePage, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppHeaderMenuIcon from '@/Components/Layout/AppHeaderMenuIcon.vue'

const { headerHeight } = variables
const page = usePage()

const dialog = ref(false)

const tabValue = ref(route().current())
</script>

<template>
    <v-app-bar
        class="px-3"
        :height="headerHeight">
        <Link href="/">
            <h3 class="font-weight-bold">
                {{ page.props.appName }}
            </h3>
        </Link>

        <v-spacer></v-spacer>

        <AppHeaderMenuIcon />
    </v-app-bar>
</template>

<style lang="scss"></style>
