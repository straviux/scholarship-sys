<template>
    <div>
        <table class="table table-zebra">
            <!-- head -->

            <tbody>
                <tr>
                    <th class="text-xs font-normal text-gray-500  w-[26%]">Status</th>
                    <td><span class="text-xs font-bold mb-4 uppercase"
                            :class="profile.scholarship_status == 0 ? 'text-orange-500' : profile.scholarship_status == 1 ? 'text-green-500' : profile.scholarship_status == 2 ? 'text-purple-500' : profile.scholarship_status == 3 ? 'text-yellow-500' : 'text-red-500'">
                            {{
                                checkStatus(profile.scholarship_status)
                            }}
                        </span></td>
                </tr>
                <tr>
                    <th class="text-xs font-normal text-gray-500 ">Program</th>
                    <td><span class="text-xs font-medium text-gray-900 mb-4">
                            {{ profile.program.shortname }}
                        </span></td>
                </tr>
                <tr>
                    <th class="text-xs font-normal text-gray-500">Course</th>
                    <td><span class="text-xs font-medium text-gray-900 mb-4">
                            {{ profile.course.shortname }}
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
                <tr>
                    <th class="text-xs font-normal text-gray-500">Remarks</th>
                    <td colspan="5"><span class="text-xs font-medium text-gray-900 mb-4 uppercase">
                            {{ profile.remarks }}
                        </span></td>
                </tr>
                <tr>
                    <th class="text-xs font-normal text-gray-500">Date Approved</th>
                    <td colspan="5"><span class="text-xs font-medium text-gray-900 mb-4 uppercase">
                            {{ profile.date_approved }}
                        </span></td>
                </tr>
            </tbody>
        </table>
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
// console.log(props.profile)
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