<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
// import { Head } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted } from "vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
// import { Draggable, OpenIcon } from "@he-tree/vue";
// import "@he-tree/vue/style/default.css";
// import "@he-tree/vue/style/material-design.css";
import Modal from "@/Components/Modal.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { ArrowUturnLeftIcon, UserPlusIcon } from "@heroicons/vue/20/solid";
import VueSelect from "vue3-select-component";
import axios from "axios";
const props = defineProps({
    profile: Object,
    voters: Object,
    searchquery: String,
});

const newDownline = ref();
const searchNameQuery = ref(props.searchquery);
const onSearchName = (option) => {
    searchNameQuery.value = option;
};

const votersOptions = ref([]);
// const votersOptions = computed(() =>
//     props.voters?.map((voter) => ({
//         label: voter.voter_name,
//         value: voter.voter_name,
//     }))
// );
const prevUrl = `/votersprofile/position/${props.profile.position.toLowerCase()}`;
const form = useForm({
    id: props.profile?.id || "",
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
    redirectUrl: `/votersprofile/position/${props.profile.position}`,
});


const newDownlineform = useForm({
    id: "",
    parent_id: "",
    name: "",
    firstname: "",
    lastname: "",
    middlename: "",
    extension: "",
    barangay: "",
    precinct_no: "",
    position: "",
});

const deleteDownlineform = useForm({
    ids: ""
})
const onSelectedDownline = (opt) => {
    // console.log(opt.data);
    const voter = opt.data;
    const lastname = voter.voter_name.split(",")[0];
    const name = voter.voter_name.split(" ").splice(1);
    const middlename = name[name.length - 1];
    const firstname = name.slice(0, -1);
    newDownlineform.parent_id = props.profile.id;
    newDownlineform.name = voter.voter_name;
    newDownlineform.barangay = voter.barangay_name;
    newDownlineform.precinct_no = voter.precinct_no;
    newDownlineform.lastname = lastname;
    newDownlineform.firstname = firstname.join(" ");
    newDownlineform.middlename = middlename;
    newDownlineform.position =
        props.profile.position == "COORDINATOR"
            ? "LEADER"
            : props.profile.position == "LEADER"
                ? "SUBLEADER"
                : "MEMBER";
};

const selectedMembers = ref([]);

const showConfirmDeleteMemberModal = ref(false);
const closeModal = () => {
    showConfirmDeleteMemberModal.value = false;
};
const prepareRemoveChildData = () => {
    showConfirmDeleteMemberModal.value = true;
};
const deleteChildData = () => {
    const getCheckedData = selectedMembers.value.map((member) => member.id);
    deleteDownlineform.ids = getCheckedData;
    deleteDownlineform.delete(route('votersprofile.bulkdelete'), {
        onSuccess: () => {
            closeModal();
            router.visit('', {
                only: ['props']
            });
        }
    }
    );
};
watch(
    () => props.profile,
    (profile) => {
        if (profile) {
            // treeData.value = props.profile?.members;
            // [
            //     {
            //         id: props.profile?.id || "",
            //         name: props.profile?.name || "",
            //         position: props.profile?.position || "",
            //         precinct_no: props.profile?.precinct_no || "",
            //         children: props.profile?.members || "",
            //     },
            // ];
            form.id = profile.id || "";
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

watch(
    searchNameQuery,
    debounce(() => {
        router.get(
            "",
            { searchname: searchNameQuery.value || newDownline.value },
            { preserveState: true, preserveScroll: true, replace: true }
        );
        votersOptions.value = props.voters.map((voter) => ({
            label: voter.voter_name,
            value: voter.voter_name,
            data: voter,
        }));
    }, 500)
);

const addDownline = () => {
    // console.log("test");
    newDownlineform.post(route("votersprofile.adddownline"), {
        onSuccess: () => {
            router.visit('', {
                only: ['props']
            })
        },
        onError: (err) => {
            console.log(err)
        }
    });
};

</script>

<template>

    <Head title="Profile" />

    <AdminLayout>
        <template #header>Profile</template>

        <div class="max-w-full mx-auto py-4">
            <Link class="underline text-gray-600 text-2xl font-semibold float-right mr-12 flex gap-2 items-baseline"
                :href="prevUrl" preserve-state preserve-scroll>
            <ArrowUturnLeftIcon class="h-5 w-5" /> Back
            </Link>
            <div class="bg-white overflow-hidden md:flex justify-evenly mt-12 md:gap-4">
                <div class="md:w-1/3 ml-12">
                    <h3 class="text-2xl mb-12">Profile Details</h3>
                    <div>
                        <InputLabel for="name" value="Name" class="text-xl" />
                        <div class="mt-1 px-2 py-3 min-h-12 border border-gray-300 rounded-lg text-xl">{{ profile.name
                            }}</div>

                    </div>
                    <div class="mt-6">
                        <InputLabel for="position" value="Position" class="text-xl" />

                        <div class="mt-1 px-2 py-3 min-h-12 border border-gray-300 rounded-lg text-xl">{{
                            profile.position }}</div>
                    </div>
                    <div class="mt-6">
                        <InputLabel for="barangay" value="Barangay" class="text-xl" />

                        <div class="mt-1 px-2 py-3 min-h-12 border border-gray-300 rounded-lg text-xl">{{
                            profile.barangay }}</div>
                    </div>
                    <div class="mt-6">
                        <InputLabel for="purok" value="Purok" class="text-xl" />

                        <div class="mt-1 px-2 py-3 min-h-12 border border-gray-300 rounded-lg text-xl">{{ profile.purok
                            }}</div>
                    </div>
                    <div class="mt-6">
                        <InputLabel for="precint" value="Precint No" class="text-xl" />

                        <div class="mt-1 px-2  py-3 min-h-12 border border-gray-300 rounded-lg text-xl">{{
                            profile.precinct_no }}</div>
                    </div>
                </div>
                <div class="w-1/2 pt-12">
                    <div v-if="props.profile.position.toLowerCase() !== 'member'"
                        class="px-12 py-8 text-gray-900 border rounded mx-auto shadow mt-16 md:mt-0">
                        <form @submit.prevent="addDownline">
                            <div class="flex items-center rounded-lg py-2 gap-2">
                                <!-- <VueMultiselect
                                v-model="filterBarangayQuery"
                                :options="barangayOptions"
                                :close-on-select="true"
                                placeholder="SELECT BARANGAY"
                            /> -->
                                <!-- {{ barangayOptions }} -->
                                <VueSelect v-model="newDownline" placeholder="Search name" :options="votersOptions"
                                    @option-selected="(option) => onSelectedDownline(option)
                                        " @search="(search) => onSearchName(search)" />
                                <button :class="{
                                    'opacity-25': !newDownline ||
                                        newDownlineform.processing ||
                                        props.profile.members.length >= 7,
                                }" :disabled="!newDownline || newDownlineform.processing ||
                                    props.profile.members.length >= 7
                                    "
                                    class="py-2 px-8 bg-[#5dbea3] hover:bg-[#4bb798] hover:shadow rounded text text-white flex gap-2 items-center">
                                    ADD
                                    <UserPlusIcon class="h-8 w-8" />
                                </button>
                            </div>
                        </form>
                        <div class="flex justify-between mt-6">
                            <div class="text-gray-400 text-xl">Downline</div>

                        </div>
                        <div>
                            <div class="py-5 flex justify-start gap-2 items-center text-xl text-gray-600 hover:bg-indigo-200 px-4"
                                v-for="(member, i) in props.profile.members" :key="`_mem_` + i">
                                <input :id="`member_checkbox_` + i" type="checkbox" v-model="selectedMembers"
                                    :value="member"
                                    class="w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2 cursor-pointer" />
                                <label :for="`member_checkbox_` + i" class="flex items-center gap-2 cursor-pointer">
                                    <p class="text-xs text-yellow-600 ml-1 tracking-wider font-bold">
                                        [{{ member.position }}]
                                    </p>
                                    <p>{{ member.name }}</p>
                                </label>
                            </div>
                        </div>

                        <div class="flex gap-4 mt-12">
                            <button class="py-1 px-4 bg-[#80669d] hover:bg-[#7b5aa0] hover:shadow rounded text-white"
                                @click.prevent="updateHeirarchy">
                                Transfer Leadership
                            </button>

                            <button class="py-2 px-4 bg-[#dd7973] hover:bg-[#d76d67] hover:shadow text-white rounded"
                                :class="{
                                    'opacity-25': !selectedMembers.length,
                                }" :disabled="!selectedMembers.length
                                    " @click.prevent="prepareRemoveChildData">
                                Remove Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Modal maxWidth="2xl" marginTop="md" :show="showConfirmDeleteMemberModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Are you sure you want to remove this data from the list?
                </h2>

                <p class="mt-4 bg-slate-100 p-2 text-center text-red-700 font-semibold">
                <p v-for="(m, i) in selectedMembers" :key="'mem_' + i" class="py-2">
                    {{ m.name }}</p>
                <!-- "{{ modalUserData.name }} / {{ modalUserData.username }}" -->
                </p>
                <div class="mt-6 flex space-x-4">
                    <DangerButton @click="deleteChildData">
                        Delete</DangerButton>
                    <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                </div>
            </div>
        </Modal>
        <!-- <Modal
            maxWidth="lg"
            :show="showConfirmDeleteMemberModal"
            @close="closeModal"
        >
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Are you sure you want to remove this data from the list?
                </h2>

                <p
                    class="mt-4 bg-slate-100 p-2 text-center text-red-700 font-semibold"
                ></p>
                <div class="mt-6 flex space-x-4">
                    <DangerButton @click="deleteChildData">
                        Delete</DangerButton
                    >
                    <SecondaryButton @click="closeModal"
                        >Cancel</SecondaryButton
                    >
                </div>
            </div>
        </Modal> -->

        <!-- <div class="md:w-1/2 lg:w-1/4">
                <div>
                    <InputLabel for="name" value="Name" />

                    <TextInput
                        id="name"
                        v-model="profile.name"
                        type="text"
                        class="mt-1 block w-full uppercase"
                    />
                </div>
                <div class="mt-6">
                    <InputLabel for="position" value="Position" />

                    <TextInput
                        id="position"
                        v-model="profile.position"
                        type="text"
                        class="mt-1 block w-full uppercase"
                    />
                </div>
                <div class="mt-6">
                    <InputLabel for="precint" value="Precint No" />

                    <TextInput
                        id="precint"
                        v-model="profile.precinct_no"
                        type="text"
                        class="mt-1 block w-full uppercase"
                    />
                </div>
            </div> -->
    </AdminLayout>
</template>
<style scoped>
:deep(.vue-select input) {
    padding: 7px 12px;
    font-size: 1.4rem;
}

:deep(.vue-select .single-value) {
    font-size: 1.4rem;
    font-weight: 500;
    padding: 7px 12px;
    color: #666666;
}

:deep(.vue-select input::placeholder) {
    color: #888;
    font-weight: 500;
    font-size: 1.4rem;
}

:deep(.vue-select .focused .menu-option .focused) {
    background: #7dd3fc;
}

:deep(.vue-select .value-container),
:deep(.vue-select .indicators-container) {
    background-color: none;
}

:deep(.vue-select .menu-option:hover) {
    background: #ddd;
}
</style>
