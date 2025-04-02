import { useEffect, useMemo, useState } from "react";
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  Pie,
  PieChart,
  ReferenceLine,
  ResponsiveContainer,
  Sector,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Button } from "./ui/button";
import DataTableBar from "./data-table-bar";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

const COLORS = [
  '#00C49F', '#0088FE', '#FFBB28', '#FF8042',
  '#FF6384', '#E74C3C', '#9966FF', '#82CA9D',
];

const renderCustomShape = (props: any) => {
  const RADIAN = Math.PI / 180;
  const { cx, cy, midAngle, innerRadius, outerRadius, startAngle, endAngle, fill, payload, percent, value } = props;
  const sin = Math.sin(-RADIAN * midAngle);
  const cos = Math.cos(-RADIAN * midAngle);
  const sx = cx + (outerRadius + 10) * cos;
  const sy = cy + (outerRadius + 10) * sin;
  const mx = cx + (outerRadius + 30) * cos;
  const my = cy + (outerRadius + 30) * sin;
  const ex = mx + (cos >= 0 ? 1 : -1) * 22;
  const ey = my;
  const textAnchor = cos >= 0 ? "start" : "end";

  return (
    <g>
      <Sector
        cx={cx}
        cy={cy}
        innerRadius={innerRadius}
        outerRadius={outerRadius}
        startAngle={startAngle}
        endAngle={endAngle}
        fill={fill}
        stroke="#fff"
        strokeWidth={2}
      />
      <Sector
        cx={cx}
        cy={cy}
        startAngle={startAngle}
        endAngle={endAngle}
        innerRadius={outerRadius + 4}
        outerRadius={outerRadius + 8}
        fill={fill}
      />
      <path d={`M${sx},${sy}L${mx},${my}L${ex},${ey}`} stroke={fill} fill="none" />
      <circle cx={ex} cy={ey} r={3} fill={fill} stroke="none" />
      <text x={ex + (cos >= 0 ? 4 : -4)} y={ey} textAnchor={textAnchor} fill="#333" fontSize={12}>
        {`${payload.name}: ${value}`}
      </text>
      <text x={ex + (cos >= 0 ? 4 : -4)} y={ey} dy={16} textAnchor={textAnchor} fill="#999" fontSize={10}>
        {`(${(percent * 100).toFixed(2)}%)`}
      </text>
    </g>
  );
};

const renderCustomTooltip = ({ active, payload }: any) => {
  if (active && payload?.length) {
    return (
      <div className="bg-white shadow-lg rounded-md p-2 border border-gray-200 text-sm">
        <p className="font-semibold text-gray-700">{payload[0].payload.name}</p>
        {payload.map((entry: any, index: number) => (
          <p key={index} style={{ color: COLORS[index] }} className="text-gray-600">
            {entry.name}: <span className="font-semibold">{entry.value}</span>
          </p>
        ))}
      </div>
    );
  }
  return null;
};

const ChartDashboard2 = ({ data }: { data: any }) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const [gdttData, setGdttData] = useState(data?.gdtt);
  const [sctdData, setSCTDData] = useState(data?.sctd);

  useEffect(() => {
    if (data?.gdtt) {
      setGdttData(data.gdtt);
      setSCTDData(data.sctd);
    }
  }, [data]);

  // const barKeys = useMemo(() => {
  //   if (!gdttData?.bar?.[0]) return [];
  //   return Object.keys(gdttData.bar[0]).filter(key => key !== "name");
  // }, [gdttData]);
  // console.log(barKeys);
  const barKeys = useMemo(() => {
    if (!gdttData?.bar?.[0]) return [];
    return Object.keys(gdttData.barDataTable[0]).filter(key => key !== "ttkv");
  }, [gdttData]);
  const barKeys1 = useMemo(() => {
    if (!sctdData?.bar?.[0]) return [];
    return Object.keys(sctdData.barDataTable[0]).filter(key => key !== "ttkv");
  }, [sctdData]);
  // console.log(barKeys);

  // Chiều cao cố định cho Pie Chart
  const pieChartHeight = 325;


  return (
    <div className="flex flex-col gap-4 p-4">
      <div className="grid gap-4 md:grid-cols-4">
        {/* Pie Chart - Chiều cao cố định */}
        <Card className="flex flex-col h-[430px]">
          <CardHeader className="items-center pb-0 pt-4">
            <CardTitle>Tỷ lệ GDTT</CardTitle>
          </CardHeader>
          <CardContent className="p-0 flex-1" style={{ height: pieChartHeight }}>
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <defs>
                  {gdttData?.pie?.map((_: any, index: number) => (
                    <linearGradient key={index} id={`pieColor${index}`} x1="0" y1="0" x2="1" y2="1">
                      <stop offset="0%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.9} />
                      <stop offset="100%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.5} />
                    </linearGradient>
                  ))}
                </defs>
                <Pie
                  data={gdttData?.pie || []}
                  cx="50%"
                  cy="50%"
                  innerRadius="30%"
                  outerRadius="50%"
                  dataKey="value"
                  labelLine={false}
                  label={renderCustomShape}
                  stroke="#fff"
                  strokeWidth={2}
                >
                  {gdttData?.pie?.map((_: any, index: number) => (
                    <Cell key={`cell-${index}`} fill={`url(#pieColor${index})`} />
                  ))}
                </Pie>
              </PieChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* Bar Chart */}
        <Card className="md:col-span-3">
          <CardHeader className="items-center pb-0 pt-4">
            <CardTitle>Tỷ lệ GDTT</CardTitle>
          </CardHeader>
          <div className="flex flex-col">
            {/* Phần Bar Chart với chiều cao cố định ban đầu */}
            <CardContent className="p-0" style={{ height: pieChartHeight }}>










              <ResponsiveContainer width="100%" height="100%">
                <BarChart
                  data={gdttData?.barDataTable || []}
                  margin={{ top: 20, right: 30, left: -10, bottom: 5 }}
                >
                  <defs>
                    {barKeys.map((_, index) => (
                      <linearGradient key={index} id={`color${index}`} x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.9} />
                        <stop offset="100%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.5} />
                      </linearGradient>
                    ))}
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.5} vertical={false} />
                  <XAxis dataKey="ttkv" />
                  <YAxis domain={[0, 50]} />
                  <Tooltip content={renderCustomTooltip} />
                  <Legend />
                  <ReferenceLine y={40} stroke="red" />
                  {barKeys.map((key, index) => (
                    <Bar
                      key={key}
                      dataKey={key}
                      fill={`url(#color${index})`}
                      radius={[8, 8, 0, 0]}
                      barSize={30}
                    />
                  ))}
                </BarChart>
              </ResponsiveContainer>










            </CardContent>
            <CardFooter className="flex flex-col items-center">
              <Button
                onClick={() => setIsExpanded(!isExpanded)}
                variant="link"
                className="text-amber-500 hover:underline mt-2"
              >
                {isExpanded ? "Read Less" : "Read More"}
              </Button>
            </CardFooter>
          </div>
        </Card>
        {isExpanded && (
          <div className="md:col-span-4 w-full">
            <DataTableBar
              columns={gdttData?.barTable || []}
              data={Object.values(gdttData?.barDataTable || {})}
            />
          </div>
        )}

        {/* Pie Chart - Chiều cao cố định */}
        <Card className="flex flex-col h-[430px]">
          <CardHeader className="items-center pb-0 pt-4">
            <CardTitle>Tỷ lệ GDTT</CardTitle>
          </CardHeader>
          <CardContent className="p-0 flex-1" style={{ height: pieChartHeight }}>
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <defs>
                  {sctdData?.pie?.map((_: any, index: number) => (
                    <linearGradient key={index} id={`pieColor${index}`} x1="0" y1="0" x2="1" y2="1">
                      <stop offset="0%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.9} />
                      <stop offset="100%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.5} />
                    </linearGradient>
                  ))}
                </defs>
                <Pie
                  data={sctdData?.pie || []}
                  cx="50%"
                  cy="50%"
                  innerRadius="30%"
                  outerRadius="50%"
                  dataKey="value"
                  labelLine={false}
                  label={renderCustomShape}
                  stroke="#fff"
                  strokeWidth={2}
                >
                  {sctdData?.pie?.map((_: any, index: number) => (
                    <Cell key={`cell-${index}`} fill={`url(#pieColor${index})`} />
                  ))}
                </Pie>
              </PieChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* Bar Chart */}
        <Card className="md:col-span-3">
          <CardHeader className="items-center pb-0 pt-4">
            <CardTitle>Tỷ lệ GDTT</CardTitle>
          </CardHeader>
          <div className="flex flex-col">
            {/* Phần Bar Chart với chiều cao cố định ban đầu */}
            <CardContent className="p-0" style={{ height: pieChartHeight }}>










              <ResponsiveContainer width="100%" height="100%">
                <BarChart
                  data={sctdData?.barDataTable || []}
                  margin={{ top: 20, right: 30, left: -10, bottom: 5 }}
                >
                  <defs>
                    {barKeys1.map((_, index) => (
                      <linearGradient key={index} id={`color${index}`} x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.9} />
                        <stop offset="100%" stopColor={COLORS[index % COLORS.length]} stopOpacity={0.5} />
                      </linearGradient>
                    ))}
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.5} vertical={false} />
                  <XAxis dataKey="ttkv" />
                  <YAxis domain={[0, 50]} />
                  <Tooltip content={renderCustomTooltip} />
                  <Legend />
                  <ReferenceLine y={40} stroke="red" />
                  {barKeys1.map((key, index) => (
                    <Bar
                      key={key}
                      dataKey={key}
                      fill={`url(#color${index})`}
                      radius={[8, 8, 0, 0]}
                      barSize={30}
                    />
                  ))}
                </BarChart>
              </ResponsiveContainer>










            </CardContent>
            <CardFooter className="flex flex-col items-center">
              <Button
                onClick={() => setIsExpanded(!isExpanded)}
                variant="link"
                className="text-amber-500 hover:underline mt-2"
              >
                {isExpanded ? "Read Less" : "Read More"}
              </Button>
            </CardFooter>
          </div>
        </Card>
        {isExpanded && (
          <div className="md:col-span-4 w-full">
            <DataTableBar
              columns={sctdData?.barTable || []}
              data={Object.values(sctdData?.barDataTable || {})}
            />
          </div>
        )}
      </div>
    </div>
  );
};

export default ChartDashboard2;