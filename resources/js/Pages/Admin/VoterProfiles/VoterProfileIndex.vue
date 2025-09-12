<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { debounce } from 'lodash';
import { router } from '@inertiajs/vue3';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { ref, onMounted, computed, watch } from 'vue';
import { usePermission } from '@/composable/permissions';
import Table from '@/Components/Table.vue';
import TableRow from '@/Components/TableRow.vue';
import TableHeaderCell from '@/Components/TableHeaderCell.vue';
import TableDataCell from '@/Components/TableDataCell.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TabLink from '@/Components/TabLink.vue';
import VueMultiselect from 'vue-multiselect';
import VueSelect from 'vue3-select-component';
import {
	MagnifyingGlassIcon,
	PencilSquareIcon,
	TrashIcon,
	IdentificationIcon,
	UserPlusIcon,
} from '@heroicons/vue/20/solid';
import { DynamicHeroicon } from 'vue-dynamic-heroicons';

import CreateModal from '@/Pages/Admin/VoterProfiles/Modal/CreateModal.vue';
import EditModal from '@/Pages/Admin/VoterProfiles/Modal/EditModal.vue';
import Pagination from '@/Components/Pagination.vue';

import { isAndroid, isIOS, isMobile } from '@basitcodeenv/vue3-device-detect'
import MobileTabLink from '@/Components/MobileTabLink.vue';

const { hasPermission } = usePermission();
const sortColumnBy = (columnName) => {
	// console.log(sortColumsDirection.value.name);
	console.log('test');
};
const props = defineProps({
	action: String,
	q: Object,
	profile: Object,
	barangays: Array,
	precincts: Array,
	voterprofiles: [Object, Array],
	search_count: [String, Number],
	total_count: [String, Number],
	voters: [Object, Array],
	coordinators: [Object, Array],
	leaders: [Object, Array],
	subleaders: [Object, Array],
	summary: [Object, Array],
});
const barangayOptions = ref([]);
const precinctOptions = computed(() =>
	props.precincts?.map((p) => ({
		label: p.precinct_no,
		value: p.precinct_no,
	}))
);
const gridview = useStorage('gridview', false);
const form = useForm({});
const currentVoterPosition = route().params.position || null;
const showConfirmDeleteVoterProfileModal = ref(false);
const modalVoterProfileData = ref({ id: null, name: null });

const filterBarangayQuery = ref(props.q?.filterbarangay?.toUpperCase());
const searchNameQuery = ref(props.q?.searchname?.toUpperCase());
const precinctNoQuery = ref(props.q?.filterprecinct?.toUpperCase());
const showResultCount = ref(props.q?.results);

const confirmDeleteVoterProfile = (profileID, profileName) => {
	showConfirmDeleteVoterProfileModal.value = true;
	modalVoterProfileData.value.id = profileID;
	modalVoterProfileData.value.name = profileName;
};
const closeModal = () => {
	showConfirmDeleteVoterProfileModal.value = false;
};
const deleteProfile = (voterProfileID) => {
	// console.log(voterProfileID);
	form.delete(route('votersprofile.destroy', voterProfileID), {
		onSuccess: () => closeModal(),
	});
};


const showFilterModal = ref(false);
const closeFilterModal = () => {
	showFilterModal.value = false;
};


watch(
	showResultCount,
	debounce(() =>
		router.get(
			'',
			{ results: showResultCount.value },
			{ preserveState: true, preserveScroll: true, replace: true }
		)
	)
);

watch(
	searchNameQuery,
	debounce(
		() =>
			router.get(
				'',
				{
					searchname: searchNameQuery.value,
				},
				{ preserveState: true, preserveScroll: true, replace: true }
			),
		500
	)
);

watch(
	filterBarangayQuery,
	debounce(() => {
		precinctNoQuery.value = null;
		router.get(
			'',
			{
				filterbarangay: filterBarangayQuery.value?.toLowerCase(),
			},
			{ preserveState: true, preserveScroll: true, replace: true }
		);
	}, 500)
);

watch(
	precinctNoQuery,
	debounce(() => {
		router.get(
			'',
			{
				filterprecinct: precinctNoQuery.value,
			},
			{ preserveState: true, preserveScroll: true, replace: true }
		);
	}, 500)
);

watch(props, () => {
	console.log(props);
});


onMounted(() => {
	// console.log(props.voterprofiles);
	console.log(props);
	barangayOptions.value = props.barangays.map((bgy) => ({
		label: bgy,
		value: bgy,
	}));
	// console.log(props.precincts);
});
</script>

<template>

	<Head title="Voter's Profile" />

	<AdminLayout>
		<template #header>Scholarship</template>


		<!-- DESKTOP VIEW -->
		<div class="max-w-full mx-auto py-4" v-if="!isMobile && !isAndroid && !isIOS">
			<div
				class="text-normal font-medium text-center text-gray-500 border-b border-gray-200 flex justify-center items-center">
				<div class="flex space-x-16 items-center text-normal mb-20">
					<MobileTabLink :href="route('votersprofile.showposition', 'all')"
						:active="route().current('votersprofile.showposition', 'all')"
						class="flex flex-col justify-center items-center">
						<button class="btn btn-circle btn-lg"
							:class="{ 'shadow-lg bg-emerald-400 text-white': route().current('votersprofile.showposition', 'all') }">A</button>

						All
						<div class="badge bg-pink-400 text-white -mt-[90px] border-2  text-[10px] -mr-12">{{ summary.all
						}}
						</div>
					</MobileTabLink>
					<MobileTabLink :href="route('votersprofile.showposition', 'coordinator')"
						:active="route().current('votersprofile.showposition', 'coordinator')"
						class="flex flex-col justify-center items-center">
						<button class="btn btn-circle btn-lg"
							:class="{ 'shadow-lg bg-emerald-400 text-white': route().current('votersprofile.showposition', 'coordinator') }">C</button>
						Coordinator<div class="badge bg-pink-400 text-white -mt-[90px] border-2  text-[10px] -mr-12">{{
							summary.coordinator
						}}</div>
					</MobileTabLink>
					<MobileTabLink :href="route('votersprofile.showposition', 'leader')"
						:active="route().current('votersprofile.showposition', 'leader')"
						class="flex flex-col justify-center items-center">
						<button class="btn btn-circle btn-lg"
							:class="{ 'shadow-lg bg-emerald-400 text-white': route().current('votersprofile.showposition', 'leader') }">L</button>Leader
						<div class="badge bg-pink-400 text-white -mt-[90px] border-2  text-[10px] -mr-12">
							{{ summary.leader }}
						</div>
					</MobileTabLink>
					<MobileTabLink :href="route('votersprofile.showposition', 'subleader')"
						:active="route().current('votersprofile.showposition', 'subleader')"
						class="flex flex-col justify-center items-center">
						<button class="btn btn-circle btn-lg"
							:class="{ 'shadow-lg bg-emerald-400 text-white': route().current('votersprofile.showposition', 'subleader') }">S</button>Subleader
						<div class="badge bg-pink-400 text-white -mt-[90px] border-2  text-[10px] -mr-12">{{
							summary.subleader }}
						</div>
					</MobileTabLink>
					<MobileTabLink :href="route('votersprofile.showposition', 'member')"
						:active="route().current('votersprofile.showposition', 'member')"
						class="flex flex-col justify-center items-center">
						<button class="btn btn-circle btn-lg"
							:class="{ 'shadow-lg bg-emerald-400 text-white': route().current('votersprofile.showposition', 'member') }">M</button>Member
						<div class="badge bg-pink-400 text-white -mt-[90px] border-2  text-[10px] -mr-12">
							{{ summary.member }}
						</div>
					</MobileTabLink>
				</div>

				<Link v-if="hasPermission('create voterprofile')" :href="route('votersprofile.showposition', {
					position: currentVoterPosition,
					action: 'create'
				})" class=" text-emerald-500 underline font-bold px-3 py-2 bg-none rounded ml-auto flex items-center justify-center
					gap-1 text-lg">
				<UserPlusIcon class="h-6 w-6" />New Profile</Link>
			</div>

			<div class="flex gap-4 mt-12 mb-4">
				<!--search bar -->
				<div class="flex gap-4 w-full">
					<div class="w-[400px]">
						<div class="relative flex items-center text-gray-400 focus-within:text-sky-500">
							<span class="absolute left-4 h-6 flex items-center pr-3 border-r border-gray-300">
								<MagnifyingGlassIcon class="h-5 w-5" />
							</span>
							<input type="search" name="leadingIcon" v-model="searchNameQuery" id="searchName"
								placeholder="Search Name"
								class="w-full pl-14 pr-4 rounded-sm text-sm text-gray-600 outline-hidden border border-gray-300 focus:border-blue-300 transition" />
						</div>
					</div>

					<div class="w-[340px] flex items-center rounded-lg">
						<!-- <VueMultiselect
                            v-model="filterBarangayQuery"
                            :options="barangayOptions"
                            :close-on-select="true"
                            placeholder="SELECT BARANGAY"
                        /> -->
						<!-- {{ barangayOptions }} -->
						<VueSelect v-model="filterBarangayQuery" placeholder="Select Barangay"
							:options="barangayOptions" />
					</div>
					<div class="w-[220px] flex items-center">
						<VueSelect :is-disabled="!filterBarangayQuery" v-model="precinctNoQuery"
							:options="precinctOptions" placeholder="Select Precinct#" />
					</div>
				</div>
				<div class="flex items-center gap-2 ml-auto px-2 mr-2" v-if="currentVoterPosition != 'all'">
					<label class="relative inline-flex cursor-pointer items-center">
						<input id="switch" type="checkbox" class="peer sr-only" v-model="gridview" />
						<label for="switch" class="hidden"></label>
						<div
							class="peer h-6 w-11 rounded-full border bg-slate-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-slate-800 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-green-300">
						</div>
					</label>
					<p class="text-xs text-gray-600" v-if="gridview">Grid</p>
					<p class="text-xs text-gray-600" v-else>Table</p>
				</div>
			</div>

			<div class="mt-10">
				<div class="flex justify-between items-baseline gap-2 mb-8" v-if="voterprofiles.data.length > 10">
					<div class="flex flex-row">
						<div class="w-[170px] space-x-2">
							<label class="text-sm text-gray-500">Show results</label>
							<select class="py-0 rounded-xs text-gray-500 border-gray-400" v-model="showResultCount">
								<option value="5">5</option>
								<option value="10">10</option>
								<option value="20">20</option>
								<option value="50">50</option>
								<option selected value="100">100</option>
								<option value="200">200</option>
							</select>
						</div>
						<div class="text-gray-500 italic border-l-2 ml-2 pl-2">
							<span class="text-gray-600 font-semibold">
								{{ search_count }}
							</span>
							<span v-if="search_count > 1"> records</span>
							<span v-else> record</span> found
						</div>
						<div class="text-gray-500 italic">
							( from
							<span class="text-gray-600 font-semibold">
								{{ total_count }}
							</span>
							total voters)
						</div>
					</div>
					<Pagination :links="voterprofiles.meta.links" v-if="voterprofiles.data.length" />
				</div>
				<ul v-if="gridview && currentVoterPosition !== 'all'" role="list"
					class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
					<li class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow-sm border border-gray-300 transition hover:-translate-y-2 duration-150 hover:shadow-lg"
						v-for="(profile, index) in voterprofiles.data" :key="'profile_' + profile.id">
						<div class="flex w-full items-start justify-between space-x-6 px-2 py-2.5">
							<p class="text-gray-400 text-xs absolute mt-1">#{{ index + 1 }}</p>
							<div class="flex-1">
								<div class="flex items-center space-x-3">
									<h3 class="text-sm font-medium text-gray-600">
										{{ profile.name }}
									</h3>
								</div>
								<p class="mt-1 font-semibold text-[12px] text-gray-500">
									{{ profile.barangay }} -
									{{ profile.precinct_no }}
								</p>
							</div>
							<!-- <img
                                class="h-10 w-10 shrink-0 rounded-full bg-gray-300"
                                src="https://qph.cf2.quoracdn.net/main-thumb-554097988-200-xietklpojlcioqxaqgcyykzfxblvoqrb.jpeg"
                                alt=""
                            /> -->
						</div>
						<div class="px-6 py-2" v-if="currentVoterPosition == 'coordinator'">
							<h3 class="text-gray-600 ml-4">Leaders</h3>
							<div class="mt-1">
								<div class="tree-view text-sm">
									<ul class="list-none pl-5">
										<li class="py-1" v-for="(leader, i) in profile.members" :key="'_leader_' + i">
											<span class="text-gray-500 text-[11px]">{{ i + 1 }}. </span>
											<span class="text-gray-700 tracking-wide text-[12px]">
												{{ leader.name }}</span>
										</li>
										<li class="py-1 text-gray-500 text-[11px]" v-if="profile.members.length < 7"
											v-for="index in 7 - profile.members.length">
											{{ profile.members.length + index }}. N/A
										</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="px-6 py-2" v-else-if="currentVoterPosition == 'leader'">
							<h3 class="text-gray-600 ml-4">SubLeaders</h3>
							<div class="mt-1">
								<div class="tree-view text-sm">
									<ul class="list-none pl-5">
										<li class="py-1" v-for="(member, i) in profile.members" :key="'_member_' + i">
											<span class="text-gray-500 text-[11px]">{{ i + 1 }}. </span>
											<span class="text-gray-700 tracking-wide text-[12px]">
												{{ member.name }}</span>
										</li>
										<li class="py-1 text-gray-500 text-[11px]" v-if="profile.members.length < 3"
											v-for="index in 3 - profile.members.length">
											{{ profile.members.length + index }}. N/A
										</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="px-6 py-2" v-else-if="currentVoterPosition == 'subleader'">
							<h3 class="text-gray-600 ml-4">Members</h3>
							<div class="mt-1">
								<div class="tree-view text-sm">
									<ul class="list-none pl-5">
										<li class="py-1" v-for="(member, i) in profile.members" :key="'_member_' + i">
											<span class="text-gray-500 text-[11px]">{{ i + 1 }}. </span>
											<span class="text-gray-700 tracking-wide text-[12px]">
												{{ member.name }}</span>
										</li>
										<li class="py-1 text-gray-500 text-[11px]" v-if="profile.members.length < 3"
											v-for="index in 3 - profile.members.length">
											{{ profile.members.length + index }}. N/A
										</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="px-4 py-2 flex" v-else-if="currentVoterPosition == 'member'">
							<p v-if="!profile.leader" class="text-sm text-red-400 font-semibold animate-pulse mx-auto">
								no
								leader
								assigned</p>
							<div v-else class="flex">
								<p class="text-gray-600 ml-4 text-[12px]">Leader:</p>
								<p class="ml-2 text-[12px] text-gray-500">
									{{ profile.leader?.name }}

								</p>
							</div>
						</div>
						<div>
							<div class="-mt-px flex divide-x divide-gray-200">
								<div v-if="hasPermission('delete voterprofile')"
									class="flex w-0 flex-1 text-red-400 hover:text-red-600">
									<button
										class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-1 rounded-bl-lg border border-transparent py-4 text-xs font-semibold"
										@click="confirmDeleteVoterProfile(profile.id, profile.name)">
										<TrashIcon class="h-5 w-5 text-red-400" />
										Delete
									</button>
								</div>
								<div class="flex w-0 flex-1 text-blue-400 hover:text-blue-600">
									<Link v-if="hasPermission('edit voterprofile')" :href="route('votersprofile.showposition', {
										position: currentVoterPosition,
										id: profile.id,
										action: 'edit'
									})
										" class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-1 rounded-bl-lg border border-transparent py-4 text-xs font-semibold"
										preserve-state preserve-scroll>
									<PencilSquareIcon class="h-5 w-5" />
									Edit
									</Link>
								</div>

								<div class="-ml-px flex w-0 flex-1 text-purple-500 hover:text-purple-600">
									<Link :href="route('votersprofile.viewprofile', {
										id: profile.id,
									})
										" class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-1 rounded-br-lg border border-transparent py-4 text-xs font-semibold">
									<IdentificationIcon class="h-5 w-5" />
									Downline
									</Link>
								</div>
							</div>
						</div>
					</li>
				</ul>

				<Table class="border-collapse border border-slate-400" v-else>
					<template #header>
						<TableRow>
							<TableHeaderCell class="w-[10px]">#</TableHeaderCell>
							<TableHeaderCell @click="sortColumnBy('name')">Name</TableHeaderCell>
							<TableHeaderCell @click="sortColumnBy('position')">Position</TableHeaderCell>
							<TableHeaderCell @click="sortColumnBy('barangay')">Barangay</TableHeaderCell>
							<TableHeaderCell @click="sortColumnBy('precinct_no')"></TableHeaderCell>
							<TableHeaderCell class="w-[160px]">Action</TableHeaderCell>
						</TableRow>
					</template>
					<template #default>
						<TableRow v-if="voterprofiles.data.length" v-for="(voter, index) in voterprofiles.data"
							:key="'voter_' + voter.id" class="hover:bg-gray-200">
							<TableDataCell class="px-6 w-[10px] border-collapse border-t border-slate-400">{{
								index + 1
							}}</TableDataCell>
							<TableDataCell class="border-collapse border-t border-l border-slate-400 indent-1">
								<div>
									{{ voter.lastname + ', ' + voter.firstname + ' ' + (voter.middlename || '') }}
								</div>
							</TableDataCell>
							<TableDataCell class="border-collapse border-t border-l border-slate-400 indent-1">
							</TableDataCell>
							<TableDataCell class="border-collapse border-t border-l border-slate-400 indent-1">{{
								voter.barangay
							}}</TableDataCell>
							<TableDataCell class="border-collapse border-t border-l border-slate-400 indent-1">
							</TableDataCell>
							<TableDataCell class="border-collapse border-t border-l border-slate-400">
								<div class="flex space-x-6 justify-center">
									<Link v-if="hasPermission('edit voterprofile')" :href="route('votersprofile.showposition', {
										position: currentVoterPosition,
										action: 'edit',
										id: voter.id,
									})
										" preserve-state preserve-scroll class="text-blue-400 hover:text-blue-600 flex">
									<PencilSquareIcon class="h-5 w-5 text-blue-400" />
									Edit
									</Link>

									<button v-if="hasPermission('delete voterprofile')"
										class="text-red-500 hover:text-red-600 flex"
										@click="confirmDeleteVoterProfile(voter.id, voter.name)">
										<TrashIcon class="h-5 w-5 text-red-400" />
										Delete
									</button>
								</div>
							</TableDataCell>
						</TableRow>
						<TableRow v-else>
							<TableDataCell
								class="px-6 py-8 w-[10px] border-collapse border-t border-slate-400 text-center"
								colspan="6">No data to be displayed</TableDataCell>
						</TableRow>
					</template>
				</Table>
				<div class="flex justify-between items-baseline gap-2 mb-6 mt-8">
					<div class="flex">
						<div class="w-[170px] space-x-2">
							<label class="text-sm text-gray-500">Show results</label>
							<select class="py-0 rounded-xs text-gray-500 border-gray-400" v-model="showResultCount">
								<option value="5">5</option>
								<option value="10">10</option>
								<option value="20">20</option>
								<option value="50">50</option>
								<option selected value="100">100</option>
								<option value="200">200</option>
							</select>
						</div>
						<div class="text-gray-500 italic border-l-2 ml-2 pl-2">
							<span class="text-gray-600 font-semibold">
								{{ search_count }}
							</span>
							<span v-if="search_count > 1"> records</span>
							<span v-else> record</span> found
						</div>
						<div class="text-gray-500 italic">
							( from
							<span class="text-gray-600 font-semibold">
								{{ total_count }}
							</span>
							total voters)
						</div>
					</div>
					<Pagination :links="voterprofiles.meta.links" v-if="voterprofiles.data.length" />
				</div>
				<EditModal v-if="props.action == 'edit' && hasPermission('edit voterprofile')" :profile="props.profile"
					:barangays="barangayOptions" :position="currentVoterPosition" />
				<CreateModal v-if="props.action == 'create' && hasPermission('create voterprofile')"
					:barangays="barangayOptions" :coordinators="props.coordinators" :leaders="props.leaders"
					:subleaders="props.subleaders" :position="currentVoterPosition" :action="props.action" />

				<!-- <EditDownlineModal v-if="props.q.showdownline" /> -->
			</div>
		</div>
		<!-- END OF DESKTOP VIEW -->

		<!-- MOBILE/ANDROID/IOS VIEW -->
		<div class="max-w-full mx-auto py-2" v-else>

			<div class="flex flex-wrap justify-between items-center text-normal mb-20">
				<MobileTabLink :href="route('votersprofile.showposition', 'all')"
					:active="route().current('votersprofile.showposition', 'all')"
					class="flex flex-col justify-center items-center">
					<button class="btn btn-circle btn-sm">A</button>

					All
					<div class="badge bg-pink-400 badge-sm text-white -mt-16 -mr-8">{{ summary.all }}</div>
				</MobileTabLink>
				<MobileTabLink :href="route('votersprofile.showposition', 'coordinator')"
					:active="route().current('votersprofile.showposition', 'coordinator')"
					class="flex flex-col justify-center items-center">
					<button class="btn btn-circle btn-sm">C</button>
					Coordinator<div class="badge bg-pink-400 badge-sm text-white -mt-16 -mr-8">{{ summary.coordinator
					}}</div>
				</MobileTabLink>
				<MobileTabLink :href="route('votersprofile.showposition', 'leader')"
					:active="route().current('votersprofile.showposition', 'leader')"
					class="flex flex-col justify-center items-center">
					<button class="btn btn-circle btn-sm">L</button>Leader<div
						class="badge bg-pink-400 badge-sm text-white -mt-16 -mr-8">
						{{ summary.leader }}
					</div>
				</MobileTabLink>
				<MobileTabLink :href="route('votersprofile.showposition', 'subleader')"
					:active="route().current('votersprofile.showposition', 'subleader')"
					class="flex flex-col justify-center items-center">
					<button class="btn btn-circle btn-sm">S</button>Subleader<div
						class="badge bg-pink-400 badge-sm text-white -mt-16 -mr-8">{{ summary.subleader }}</div>
				</MobileTabLink>
				<MobileTabLink :href="route('votersprofile.showposition', 'member')"
					:active="route().current('votersprofile.showposition', 'member')"
					class="flex flex-col justify-center items-center">
					<button class="btn btn-circle btn-sm">M</button>Member<div
						class="badge bg-pink-400 badge-sm text-white -mt-16 -mr-8">
						{{ summary.member }}
					</div>
				</MobileTabLink>
			</div>

			<!-- POSITION SELECTION/SEARCH NAME -->
			<div class="join flex  shadow-sm border">


				<button class="btn join-item bg-purple-500 text-white border-0" @click="
					showFilterModal = !showFilterModal
					">
					<DynamicHeroicon name="filter" :outline="true" :size="6" />
				</button>
				<input class="input join-item w-full focus:outline-hidden" placeholder="Search name"
					v-model="searchNameQuery" />
				<!-- <select class="select text-xs select-bordered focus:outline-hidden join-item w-1/4">
					<option disabled selected>Bgy</option>
					<option v-for="bgy in barangays">{{ bgy }}</option>

				</select>
				<select class="select text-xs select-bordered focus:outline-hidden join-item w-1/3">
					<option disabled selected>Precinct</option>
					<option v-for="bgy in barangays">{{ bgy }}</option>

				</select> -->
				<button class="btn join-item bg-emerald-500 text-white border-0">
					<DynamicHeroicon name="plus" :outline="true" :size="6" />
				</button>
			</div>

			<!-- LIST RESULT -->
			<div class="mt-8">
				<!-- <div v-for="(profile, index) in voterprofiles.data" :key="'profile_' + profile.id" class="py-1">
					{{ profile.name }}
				</div> -->
				<div v-for="(profile, index) in voterprofiles.data" :key="'profile_' + profile.id"
					class="px-2 py-3 border-b flex flex-col gap-2">
					<div class="flex">
						<p class="text-gray-600 w-[30px] text-xs mt-1 -ml-1">{{ index + 1 }}. </p>
						<div class="w-full flex justify-between">
							<p class="pr-2">{{ profile.name }}</p>
							<p class="font-semibold">{{ profile.precinct_no }}</p>
						</div>
					</div>
					<div class="text-xs ml-7 text-gray-500 flex justify-between">
						<div>{{ profile.barangay }}</div>
						<div class="font-semibold">{{ profile.position }}</div>
					</div>
				</div>
			</div>

		</div>
		<Modal marginTop="md" maxWidth="lg" :show="showFilterModal" @close="closeFilterModal">
			<div class="p-4">
				<h2 class="text-lg font-semibold text-slate-800">
					Filter Options
				</h2>

				<div class="mt-6 flex justify-between">
					<div class="flex items-center gap-1">
						<label class="text-xs text-gray-500">Show Results</label>
						<select class="py-0 rounded-xs text-gray-500 text-sm border-gray-400" v-model="showResultCount">
							<option value="5">5</option>
							<option value="10">10</option>
							<option value="20">20</option>
							<option value="50">50</option>
							<option selected value="100">100</option>
							<option value="200">200</option>
						</select>
					</div>

					<!-- <div class="flex gap-1 items-center">
						<label class="text-xs text-gray-5">Voters List</label>
						<select class="py-0 rounded-xs text-gray-500 text-sm border-gray-400" v-model="list_year">
							<option value="2024">2024</option>
							<option selected value="2025">2025</option>
						</select>
					</div> -->
				</div>

				<div class="mt-6">
					<VueSelect v-model="filterBarangayQuery" placeholder="Select Barangay" :options="barangayOptions" />

				</div>

				<div class="mt-6">
					<VueSelect :is-disabled="!filterBarangayQuery" v-model="precinctNoQuery" :options="precinctOptions"
						placeholder="Select Precinct#" />

				</div>

				<div class="mt-16 flex space-x-4 justify-end">
					<SecondaryButton @click="closeFilterModal">close</SecondaryButton>
				</div>
			</div>
		</Modal>
		<!-- END MOBILE/ANDROID/IOS VIEW -->
		<Modal v-if="hasPermission('delete voterprofile')" maxWidth="lg" :show="showConfirmDeleteVoterProfileModal"
			@close="closeModal">
			<div class="p-6">
				<h2 class="text-lg font-semibold text-slate-800">
					Are you sure you want to delete this profile?
				</h2>
				<p class="mt-4 bg-slate-100 p-2 text-center text-red-700 font-semibold">
					"{{ modalVoterProfileData.name }}"
				</p>

				<div class="mt-6 flex space-x-4">
					<DangerButton @click="deleteProfile(modalVoterProfileData.id)"> Delete</DangerButton>
					<SecondaryButton @click="closeModal">Cancel</SecondaryButton>
				</div>
			</div>
		</Modal>
	</AdminLayout>
</template>
<style scoped>
/* .active {
	background: red;
} */

/* .multiselect__input {
    min-height: 24px !important;
    font-size: 12px !important;
    border-radius: 8px !important;
}
.multiselect__option {
    font-size: 14px !important;
}

.multiselect__input::placeholder {
    text-transform: none;
} */
#searchName::placeholder {
	color: #888;
	font-weight: 400;
	font-size: 1rem;
}

:deep(.vue-select input) {
	padding: 7px 10px;
}

:deep(.vue-select input::placeholder) {
	color: #888;
	font-weight: 400;
	font-size: 1rem;
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

<style src="vue-multiselect/dist/vue-multiselect.css"></style>
