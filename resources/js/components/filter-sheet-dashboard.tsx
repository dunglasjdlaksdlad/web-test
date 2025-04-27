import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
  SheetFooter,
} from "@/components/ui/sheet";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { MultiSelect } from "@/components/ui/multiple-selector";
import { useForm } from "@inertiajs/react";
import { Filter, Loader2 } from "lucide-react";
import { DialogProps } from "@radix-ui/react-dialog";
import { FormEvent, useMemo, useEffect, useState } from "react";
import { FormTypeDashboard } from "@/types";
import { createPortal } from "react-dom";
import { format } from "date-fns";
import { CalendarIcon } from "lucide-react";
import { Calendar } from "@/components/ui/calendar";
import {
  Popover,
  PopoverTrigger,
  PopoverContent,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";
import { SmartDatetimeInput } from "./ui/smart-datetime-input";


type FilterSheetProps = {
  areas: { name: string; districts: { name: string }[] }[];
  onFilterApply: (filters: FormTypeDashboard) => void;
};

const mscOptions = [
  { value: "GDTT", label: "GDTT" },
  { value: "SCTD", label: "SCTD" },
  { value: "CDBR", label: "CDBR" },
  { value: "WOTT", label: "WOTT" },
  { value: "PAKH", label: "PAKH" },
];

const FilterSheet = ({
  onOpenChange,
  areas,
  onFilterApply,
  ...props
}: DialogProps & FilterSheetProps) => {
  const { data, setData, processing } = useForm<FormTypeDashboard>({
    msc: [],
    areas: [],
    districts: [],
    // startDate: "",
    // endDate: "",
    time: {
      // startDate: "",
      // endDate: "",
      0: "",
      1: "",
    },
  });

  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
    return () => setMounted(false);
  }, []);

  const mappedAreas = useMemo(
    () =>
      [{ name: "HCM", label: "HCM" }, ...areas].map((area) => ({
        value: area.name,
        label: area.name,
      })),
    [areas]
  );

  const areaDistrictsMap = useMemo(
    () =>
      areas.flatMap((area) =>
        Array.isArray(area.districts)
          ? area.districts.map((district: any) => ({
            value: district.name2,
            label: district.name2,
          }))
          : []
      ),
    [areas]
  );

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    onFilterApply(data);
    onOpenChange?.(false);
  };

  const filterSheetContent = (
    <div className="fixed bottom-6 right-6 z-50">
      <Sheet onOpenChange={onOpenChange} {...props}>
        <SheetTrigger asChild>
          <Button className="w-11 h-11 rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 flex items-center justify-center transition-all duration-300 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <Filter className="w-6 h-6" />
          </Button>
        </SheetTrigger>

        <SheetContent className="overflow-y-auto">
          <SheetHeader className="sticky top-0 bg-background z-10 pb-4">
            <SheetTitle>Filter Options</SheetTitle>
            <SheetDescription>
              Select your desired filters and click "Apply".
            </SheetDescription>
          </SheetHeader>

          <form className="grid gap-4 px-4" onSubmit={handleSubmit}>
            <div className="space-y-2">
              <Label htmlFor="msc">MSC</Label>
              <MultiSelect
                id="msc"
                options={mscOptions}
                onValueChange={(values) => setData("msc", values)}
                defaultValue={data.msc}
                placeholder="Select MSC"
                className="w-full"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="areas">Areas</Label>
              <MultiSelect
                id="areas"
                options={mappedAreas}
                onValueChange={(values) => setData("areas", values)}
                defaultValue={data.areas}
                placeholder="Select Areas"
                className="w-full"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="districts">Districts</Label>
              <MultiSelect
                id="districts"
                options={areaDistrictsMap}
                onValueChange={(values) => setData("districts", values)}
                defaultValue={data.districts}
                placeholder="Select Districts"
                className="w-full"
              />
            </div>

            {/* <div className="space-y-2">
              <Label>Date Range</Label>
              <SmartDatetimeInput
                mode="range"
                value={{
                  from: data.time[0] && !isNaN(new Date(data.time[0]).getTime()) ? new Date(data.time[0]) : undefined,
                  to: data.time[1] && !isNaN(new Date(data.time[1]).getTime()) ? new Date(data.time[1]) : undefined,
                }}
                onValueChange={(value) => {
                  if ("from" in value) {
                    setData("time", {
                      ...data.time,
                      0: value.from ? value.from.toISOString() : "",
                      1: value.to ? value.to.toISOString() : "",
                    });
                  }
                }}

              />
            </div> */}
            <div className="space-y-2">
              <Label>Date Range</Label>
              <SmartDatetimeInput
                mode="range"
                value={{
                  from: data.time[0] && !isNaN(Number(data.time[0])) ? new Date(Number(data.time[0])) : undefined,
                  to: data.time[1] && !isNaN(Number(data.time[1])) ? new Date(Number(data.time[1])) : undefined,
                }}
                onValueChange={(value) => {
                  if ("from" in value) {
                    setData("time", {
                      ...data.time,
                      0: value.from ? value.from.getTime().toString() : "",
                      1: value.to ? value.to.getTime().toString() : "",
                    });
                  }
                }}
                placeholder="e.g. from tomorrow at 3pm to next week"
              />
            </div>

            <SheetFooter className="sticky bottom-0 bg-background ">
              <Button
                type="submit"
                disabled={processing}
                className="w-full"
              >
                {processing ? (
                  <Loader2 className="w-4 h-4 animate-spin mr-2" />
                ) : null}
                Apply Filters
              </Button>
            </SheetFooter>
          </form>
        </SheetContent>
      </Sheet>
    </div>
  );

  return mounted ? createPortal(filterSheetContent, document.body) : null;
};

export default FilterSheet;