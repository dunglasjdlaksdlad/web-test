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
import { FormEvent, useMemo } from "react";
import { FormTypeDashboard } from "@/types";

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
    startDate: "",
    endDate: "",
  });

  // Memoize mappedAreas và areaDistrictsMap để tránh tính toán lại
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

  return (
    <div className="fixed bottom-4 right-4 z-50">
      <Sheet onOpenChange={onOpenChange} {...props}>
        <SheetTrigger asChild>
          <Button className="w-10 h-10 rounded-full bg-blue-800 text-white shadow-lg hover:bg-blue-900 flex items-center justify-center">
            <Filter className="w-5 h-5" />
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

            <div className="space-y-2">
              <Label htmlFor="startDate">Start Date</Label>
              <Input
                id="startDate"
                type="datetime-local"
                value={data.startDate}
                onChange={(e) => setData("startDate", e.target.value)}
                className="w-full"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="endDate">End Date</Label>
              <Input
                id="endDate"
                type="datetime-local"
                value={data.endDate}
                onChange={(e) => setData("endDate", e.target.value)}
                className="w-full"
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
};

export default FilterSheet;