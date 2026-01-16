<template>
    <div>
        <table class="table table-zebra">
            <!-- head -->

            <tbody>
                <tr>
                    <th class="text-xs font-normal text-gray-500  w-[25%]">Status</th>
                    <td><span class="text-xs font-bold mb-4 uppercase"
                            :class="profile.unified_status === 'pending' ? 'text-orange-500' : profile.unified_status === 'active' ? 'text-green-500' : profile.unified_status === 'approved' ? 'text-blue-500' : profile.unified_status === 'completed' ? 'text-yellow-500' : profile.unified_status === 'denied' ? 'text-red-500' : 'text-gray-500'">
                            {{
                                checkStatus(profile.unified_status)
                            }}
                        </span></td>
                </tr>
                <tr>
                    <th class="text-xs font-normal text-gray-500  w-[25%]">Program</th>
                    <td><span class="text-xs font-medium text-gray-900 mb-4">
                            {{ profile }}
                        </span></td>
                </tr>
                <tr>
                    <th class="text-xs font-normal text-gray-500">Course</th>
                    <td><span class="text-xs font-medium text-gray-900 mb-4">
                            {{ profile }}
                        </span></td>

                </tr>
                <tr>
                    <th class="text-xs font-normal text-gray-500">
                        Academic Year
                    </th>
                    <td><span class="text-xs font-medium text-gray-900 mb-4">
                            {{ profile.academic_year }}
                        </span></td>


                </tr>
                <tr>
                    <th class="text-xs font-normal text-gray-500">
                        Year Level
                    </th>
                    <td><span class="text-xs font-medium text-gray-900 mb-4">
                            {{ profile.year_level }}
                        </span></td>


                </tr>
                <tr>
                    <th class="text-xs font-normal text-gray-500">
                        Term
                    </th>
                    <td><span class="text-xs font-medium text-gray-900 mb-4">
                            {{ profile.term }}
                        </span></td>


                </tr>

                <tr>
                    <th class="text-xs font-normal text-gray-500">School</th>
                    <td colspan="5"><span class="text-xs font-medium text-gray-900 mb-4 uppercase">
                            {{ profile.school_name }}
                        </span></td>
                </tr>
                <tr>
                    <th class="text-xs font-normal text-gray-500">Company</th>
                    <td colspan="5"><span class="text-xs font-medium text-gray-900 mb-4 uppercase">
                            {{ profile.company_name }}
                        </span></td>
                </tr>
            </tbody>
        </table>

        <div class="w-full flex mt-12">
            <div class="text-xs text-gray-500">
                <span class="underline">Requirements:</span>
                <div class="mt-4 flex items-center gap-4 w-full bg-grey-lighter">
                    <p class="font-semibold text-gray-600">1. Letter of Intent:</p>
                    <label v-if="hasPermission('edit-scholar-profile')">
                        <p
                            class="px-2 text-sm font-medium leading-normal text-blue-500 cursor-pointer underline underline-offset-2 flex items-center gap-1">
                            <CloudArrowUpIcon class="h-4 w-4" /> upload file
                        </p>
                        <input type='file' class="hidden" />
                    </label>
                </div>
                <div class="mt-2 flex items-center gap-4 w-full bg-grey-lighter">
                    <p class="font-semibold text-gray-600">2. Certificate of Indigency:
                    </p>
                    <label v-if="hasPermission('edit-scholar-profile')">
                        <p
                            class="px-2 text-sm font-medium leading-normal text-blue-500 cursor-pointer underline underline-offset-2 flex items-center gap-1">
                            <CloudArrowUpIcon class="h-4 w-4" /> upload file
                        </p>
                        <input type='file' class="hidden" />
                    </label>
                </div>
                <div class="mt-2 flex items-center gap-4 w-full bg-grey-lighter">
                    <p class="font-semibold text-gray-600">3. Certificate of Residency:
                    </p>
                    <label v-if="hasPermission('edit-scholar-profile')">
                        <p
                            class="px-2 text-sm font-medium leading-normal text-blue-500 cursor-pointer underline underline-offset-2 flex items-center gap-1">
                            <CloudArrowUpIcon class="h-4 w-4" /> upload file
                        </p>
                        <input type='file' class="hidden" />
                    </label>
                </div>
                <div class="mt-2 flex items-center gap-4 w-full bg-grey-lighter">
                    <p class="font-semibold text-gray-600">4. Copy of Grades:</p>
                    <label v-if="hasPermission('edit-scholar-profile')">
                        <p
                            class="px-2 text-sm font-medium leading-normal text-blue-500 cursor-pointer underline underline-offset-2 flex items-center gap-1">
                            <CloudArrowUpIcon class="h-4 w-4" /> upload file
                        </p>
                        <input type='file' class="hidden" />
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { usePermission } from '@/composable/permissions';
import { CloudArrowUpIcon } from "@heroicons/vue/20/solid";
const props = defineProps({
    profile: Object,
    action: String,
    message: Object,
});
const { hasPermission } = usePermission();
const checkStatus = (status) => {
    //'0: Pending, 1: Approved/Ongoing, 2: Completed, 3: Suspended, 4: Cancelled');
    switch (status) {
        case 0:
            return 'Pending';
        case 1:
            return 'Approved/Ongoing';
        case 2:
            return 'Completed';
        case 3:
            return 'Suspended';
        case 4:
            return 'Cancelled';
        default:
            return 'Unknown';
    }
};
</script>

<style lang="scss" scoped></style>