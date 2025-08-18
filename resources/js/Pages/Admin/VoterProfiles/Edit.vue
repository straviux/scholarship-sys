<script setup>
import { onMounted, ref, watch } from "vue";

import { Head, useForm } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import VueMultiselect from "vue-multiselect";
import { ArrowUturnLeftIcon } from "@heroicons/vue/20/solid";
const props = defineProps({
    // voters: Object,
    // coordinators: Object,
    // leaders: Object,
    // subleaders: Object,
    barangays: Array,
    profile: { type: Object, required: true },
});
const positions = ["COORDINATOR", "LEADER", "SUBLEADER", "MEMBER"];

const barangayOptions = ref([]);
const coordinator = ref([]);
const leader = ref([]);
const subleader = ref([]);
const voter_name = ref([]);
const form = useForm({
    parent_id: props.profile?.parent_id || "",
    name: props.profile?.name,
    firstname: props.profile?.firstname,
    lastname: props.profile?.lastname,
    middlename: props.profile?.middlename || "",
    barangay: props.profile?.barangay,
    birthdate: props.profile?.birthdate || "",
    purok: props.profile?.purok || "",
    contact_no: props.profile?.contact_no || "",
    precinct_no: props.profile?.precinct_no || "",
    gender: props.profile?.gender || "",
    position: props.profile?.position || "",
    remarks: props.profile?.remarks || "",
});

// const searchVoterQuery = ref();
// const searchCoordinatorQuery = ref();
// const searchLeaderQuery = ref();
// const searchSubleaderQuery = ref();

// const searchVoter = (voter) => {
//     searchVoterQuery.value = voter;
// };

// const searchCoordinator = (voter) => {
//     searchCoordinatorQuery.value = voter;
// };

// const searchLeader = (voter) => {
//     searchLeaderQuery.value = voter;
// };

// const searchSubleader = (voter) => {
//     searchSubleaderQuery.value = voter;
// };

// const onSelectVoter = (voter) => {
//     // console.log(voter.voter_name.split(","));
//     const lastname = voter.voter_name.split(",")[0];
//     const name = voter.voter_name.split(" ").splice(1);
//     const middlename = name[name.length - 1];
//     const firstname = name.slice(0, -1);
//     form.name = voter.voter_name;
//     form.barangay = voter.barangay_name;
//     form.precinct_no = voter.precinct_no;
//     form.lastname = lastname;
//     form.firstname = firstname.join(" ");
//     form.middlename = middlename;
//     console.log(voter);
//     // console.log(name.length);
// };

// const onSelectParent = (c) => {
//     form.parent_id = c?.id;
// };

// watch(
//     searchVoterQuery,
//     debounce(
//         (q) =>
//             router.get(
//                 "create",
//                 { voter: q },
//                 { preserveState: true, preserveScroll: true, replace: true }
//             ),
//         500
//     )
// );

onMounted(() => {
    // console.log(props.profile);
    barangayOptions.value = props.barangays.map((bgy) => bgy.barangay_name);
    // console.log(barangayOptions.value);
});
</script>

<template>
    <Head title="Create new role" />

    <AdminLayout>
        <template #header>Voter Profile</template>

        <div class="max-w-5xl mx-auto py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-indigo-700">
                    Edit Voter Profile
                </h1>
                <button
                    onclick="window.history.back()"
                    class="text-slate-500 underline font-bold px-3 py-2 bg-none rounded flex items-center justify-center gap-1"
                >
                    <ArrowUturnLeftIcon class="h-5 w-5" /> Back
                </button>
            </div>
            <div
                class="mt-6 max-w-5xl mx-auto bg-gray-100 shadow-lg rounded-lg p-6"
            >
                <form
                    @submit.prevent="
                        form.put(route('votersprofile.update', profile.id))
                    "
                >
                    <div class="mt-4 flex gap-2">
                        <div class="w-1/2">
                            <InputLabel for="position" value="Position" />
                            <VueMultiselect
                                v-model="form.position"
                                :options="positions"
                                :close-on-select="true"
                                class="uppercase"
                                placeholder="Select position"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.position"
                            />
                        </div>
                        <!-- <div class="w-1/2" v-if="form.position == 'LEADER'">
                            <InputLabel for="coordinator" value="Coordinator" />
                            <VueMultiselect
                                v-model="coordinator"
                                @update:model-value="onSelectParent"
                                :options="props.coordinators"
                                :close-on-select="true"
                                label="firstname"
                                placeholder="Search coordinator"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.coordinator_id"
                            />
                        </div>
                        <div class="w-1/2" v-if="form.position == 'SUBLEADER'">
                            <InputLabel for="leader" value="Leader" />
                            <VueMultiselect
                                v-model="leader"
                                @update:model-value="onSelectParent"
                                :options="props.leaders"
                                :close-on-select="true"
                                label="firstname"
                                placeholder="Search Leader"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.position"
                            />
                        </div> -->
                        <!-- <div class="w-1/2" v-if="form.position == 'MEMBER'">
                            <InputLabel for="subleader" value="Subleader" />
                            <VueMultiselect
                                v-model="subleader"
                                @update:model-value="onSelectParent"
                                :options="props.subleaders"
                                :close-on-select="true"
                                label="firstname"
                                placeholder="Search subleader"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.position"
                            />
                        </div> -->
                    </div>
                    <div class="w-full mt-4">
                        <!-- <InputLabel for="voters" value="Voter" />

                        <VueMultiselect
                            v-model="voter_name"
                            @search-change="searchVoter"
                            @update:model-value="onSelectVoter"
                            :options="props.voters"
                            :close-on-select="true"
                            label="voter_name"
                            placeholder="Search voter"
                        />

                        <InputError
                            class="mt-2"
                            :message="form.errors.barangay"
                        /> -->
                    </div>
                    <div class="mt-4 flex justify-between gap-4">
                        <div class="w-1/3">
                            <InputLabel for="lastname" value="Last Name" />

                            <TextInput
                                autofocus
                                id="lastname"
                                type="text"
                                class="mt-1 block w-full uppercase"
                                v-model="form.lastname"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.lastname"
                            />
                        </div>
                        <div class="w-1/3">
                            <InputLabel for="firstname" value="First Name" />

                            <TextInput
                                id="firstname"
                                type="text"
                                class="mt-1 block w-full uppercase"
                                v-model="form.firstname"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.firstname"
                            />
                        </div>
                        <div class="w-1/3">
                            <InputLabel for="middlename" value="Middle Name" />

                            <TextInput
                                id="middlename"
                                type="text"
                                class="mt-1 block w-full uppercase"
                                v-model="form.middlename"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.middlename"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between gap-4">
                        <div class="w-1/3">
                            <InputLabel for="barangay" value="Barangay" />

                            <VueMultiselect
                                v-model="form.barangay"
                                :options="barangayOptions"
                                :close-on-select="true"
                                placeholder="Select barangay"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.barangay"
                            />
                        </div>
                        <div class="w-1/3">
                            <InputLabel for="purok" value="Purok/Sitio" />

                            <TextInput
                                id="purok"
                                type="text"
                                class="mt-1 block w-full uppercase"
                                v-model="form.purok"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.purok"
                            />
                        </div>
                        <div class="w-1/3">
                            <InputLabel
                                for="contact_no"
                                value="Contact Number"
                            />

                            <TextInput
                                id="contact_no"
                                type="text"
                                class="mt-1 block w-full uppercase"
                                v-model="form.contact_no"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.contact_no"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between gap-4">
                        <div class="w-1/3">
                            <InputLabel
                                for="precinct_no"
                                value="Precinct Number"
                            />

                            <TextInput
                                id="precinct_no"
                                type="text"
                                class="mt-1 block w-full uppercase"
                                v-model="form.precinct_no"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.precinct_no"
                            />
                        </div>
                        <div class="w-1/3">
                            <InputLabel for="birthdate" value="Birthdate" />

                            <TextInput
                                id="birthdate"
                                type="date"
                                class="mt-1 block w-full uppercase"
                                v-model="form.birthdate"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.birthdate"
                            />
                        </div>
                        <div class="w-1/3">
                            <InputLabel for="gender" value="Gender" />

                            <div class="flex gap-4 mt-4 ml-4">
                                <div
                                    class="flex items-center mb-4 cursor-pointer"
                                >
                                    <input
                                        v-model="form.gender"
                                        type="radio"
                                        name="gender"
                                        value="M"
                                        class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300 cursor-pointer"
                                        aria-labelledby="country-option-1"
                                        aria-describedby="country-option-1"
                                    />
                                    <label
                                        for="country-option-1"
                                        class="text-sm font-medium text-gray-900 ml-2 block cursor-pointer"
                                    >
                                        Male
                                    </label>
                                </div>

                                <div class="flex items-center mb-4">
                                    <input
                                        v-model="form.gender"
                                        type="radio"
                                        name="gender"
                                        value="F"
                                        class="h-4 w-4 border-gray-300 focus:ring-2 focus:ring-blue-300 cursor-pointer"
                                        aria-labelledby="country-option-2"
                                        aria-describedby="country-option-2"
                                    />
                                    <label
                                        for="country-option-2"
                                        class="text-sm font-medium text-gray-900 ml-2 block cursor-pointer"
                                    >
                                        Female
                                    </label>
                                </div>
                            </div>
                            <InputError
                                class="mt-2"
                                :message="form.errors.gender"
                            />
                        </div>
                    </div>

                    <div class="mt-6 w-1/2">
                        <InputLabel for="remarks" value="Remarks" />

                        <TextInput
                            id="remarks"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.remarks"
                        />

                        <InputError
                            class="mt-2"
                            :message="form.errors.remarks"
                        />
                    </div>
                    <div class="flex items-center mt-4">
                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Update
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
<style>
.multiselect__input {
    min-height: 40px !important;
    text-transform: uppercase;
    border-radius: 8px !important;
}
.multiselect__input::placeholder {
    text-transform: none;
}
</style>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
