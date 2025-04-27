
import ChartDashboard from '@/components/chart-dashboard';
import ChartDashboard2 from '@/components/chart-dashboard2';
import ChartDashboard1 from '@/components/chart-dashboard2';
import ChartDashboard3 from '@/components/chart-dashboard3';
import FilterSheet from '@/components/filter-sheet-dashboard';
import FilterSheet1234 from '@/components/filter-sheet-dashboard';
import Loading from '@/components/ui/loading';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { FormTypeDashboard, type BreadcrumbItem } from '@/types';
import { Deferred, Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Bar, BarChart, CartesianGrid, Legend, Pie, PieChart, ReferenceLine, Sector, Tooltip, XAxis, YAxis } from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];


type Props = {
    areas: any[];
    data: any[];
};
export default function Dashboard({ areas, data }: Props) {
    const user = usePage().props.auth.user;
    const [openFilter, setOpenFilter] = useState(false);
    const [kdata, setKdata] = useState(data);
    const [filters, setFilters] = useState<FormTypeDashboard | undefined>(undefined);

 
    // console.log(data, areas);

    useEffect(() => {
        setKdata(data);
    }, [data]);

    const handleFilterApply = useCallback(async (filters: FormTypeDashboard) => {
        try {
             setFilters(filters);
            //  console.log(filters);
            const response = await axios.get(route("dashboard.filter"), { params: filters });
            if (response.status === 200) {
                setKdata(response.data);
            }
        } catch (error) {
            console.error("Lỗi khi lấy dữ liệu:", error);
        }
    }, []);

    const memoizedData = useMemo(() => kdata, [kdata]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <Deferred data="data" fallback={<Loading />}>
                <div className=" container mx-auto pt-4 px-4 sm:px-4 lg:px-0">
                    <ChartDashboard3 data={memoizedData} filters={filters}/>
                </div>
            </Deferred>
            <FilterSheet
                open={openFilter}
                onOpenChange={setOpenFilter}
                areas={areas}
                onFilterApply={handleFilterApply}
            />

        </AppLayout>
    );
}
