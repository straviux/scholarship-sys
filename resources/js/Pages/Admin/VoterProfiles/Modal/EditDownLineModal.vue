<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" class="relative z-10">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex justify-center p-4 text-center">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h3"
                                class="text-xl font-medium leading-6 text-gray-900 flex justify-between"
                            >
                                Edit Voter's Profile
                                <Link
                                    class="-mr-4 -mt-4"
                                    :href="
                                        route('votersprofile.showposition', {
                                            position: props?.position,
                                        })
                                    "
                                    preserve-state
                                    preserve-scroll
                                >
                                    <XCircleIcon class="h-8 w-8 text-red-400" />
                                </Link>
                            </DialogTitle>
                            <form
                                @submit.prevent="
                                    form.put(
                                        route(
                                            'votersprofile.update',
                                            profile.id
                                        ),
                                        { preserveScroll: true }
                                    )
                                "
                            >
                                <Draggable
                                    class="mtl-tree"
                                    v-model="treeData"
                                    treeLine
                                >
                                    <template #default="{ node, stat }">
                                        <OpenIcon
                                            v-if="stat.children.length"
                                            :open="stat.open"
                                            class="mtl-mr -ml-4"
                                            @click.native="
                                                stat.open = !stat.open
                                            "
                                        />
                                        <input
                                            class="mtl-checkbox mtl-mr"
                                            type="checkbox"
                                            v-model="stat.checked"
                                        />
                                        <span class="mtl-ml">{{
                                            node.name
                                        }}</span>
                                    </template>
                                </Draggable>

                                <button
                                    class="py-2 px-4 bg-purple-700 text-white"
                                    @click.prevent="updateHeirarchy"
                                >
                                    Update Heirarchy
                                </button>

                                <div class="flex items-center mt-4 justify-end">
                                    <PrimaryButton
                                        :class="{
                                            'opacity-25': form.processing,
                                        }"
                                        class="w-32 justify-center"
                                        :disabled="form.processing"
                                    >
                                        Update
                                    </PrimaryButton>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { BaseTree, Draggable, pro, OpenIcon } from "@he-tree/vue";
import "@he-tree/vue/style/default.css";
import "@he-tree/vue/style/material-design.css";
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
} from "@headlessui/vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import VueMultiselect from "vue-multiselect";

import { XCircleIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
    profile: Object,
    downline: Boolean,
    position: String,
    barangays: Array,
});

const isOpen = computed(() => !!props.downline);
const treeData = ref([
    {
        id: props.profile?.id || "",
        name: props.profile?.name || "",
        children: props.profile?.members || "",
    },
]);

const updateHeirarchy = () => {
    console.log(treeData);
};

const positions = ["COORDINATOR", "LEADER", "SUBLEADER", "MEMBER"];

const form = useForm({
    parent_id: props.profile?.parent_id || "",
    name: props.profile?.name || "",
    firstname: props.profile?.firstname || "",
    lastname: props.profile?.lastname || "",
    middlename: props.profile?.middlename || "",
    barangay: props.profile?.barangay || "",
    birthdate: props.profile?.birthdate || "",
    purok: props.profile?.purok || "",
    contact_no: props.profile?.contact_no || "",
    precinct_no: props.profile?.precinct_no || "",
    gender: props.profile?.gender || "",
    position: props.profile?.position || "",
    remarks: props.profile?.remarks || "",
    redirectUrl: `/votersprofile/position/${props.position}`,
});

watch(
    () => props.profile,
    (profile) => {
        if (profile) {
            treeData.value = [
                {
                    id: props.profile?.id || "",
                    name: props.profile?.name || "",
                    children: props.profile?.members || "",
                },
            ];
            form.parent_id = profile.parent_id || "";
            form.name = profile.name || "";
            form.firstname = profile.firstname || "";
            form.lastname = profile.lastname || "";
            form.middlename = profile.middlename || "";
            form.barangay = profile.barangay || "";
            form.birthdate = profile.birthdate || "";
            form.purok = profile.purok || "";
            form.contact_no = profile.contact_no || "";
            form.precinct_no = profile.precinct_no || "";
            form.gender = profile.gender || "";
            form.position = profile.position || "";
            form.remarks = profile.remarks || "";
            form.redirectUrl = `/votersprofile/position/${props.position}`;
        }
    }
);
</script>
