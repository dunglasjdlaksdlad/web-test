import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { useForm } from "@inertiajs/react";
import { DialogProps } from "@radix-ui/react-dialog";
import { Input } from "./ui/input";
import { Label } from "./ui/label";
import FormError from "./form-error";
import { Button } from "./ui/button";
import { Loader2, Minus } from "lucide-react";
import { FormEvent } from "react";
import { Toaster, toast } from "react-hot-toast";
import { Plus } from "lucide-react";
// import { IconButton } from "@mui/material";
// import { FaMinusCircle, FaPlusCircle } from "react-icons/fa";

import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
type FormType = {
  name1: string;
  name: { value: string }[];
  framework: string;
};

const frameworkItem = [
  'Dashboard & Reports',
  'Content',
  'User Management'
]

const AddPermissionSheet = ({ onOpenChange, ...props }: DialogProps) => {
  const { data, setData, post, errors, reset, processing } = useForm<FormType>({
    name1: "",
    name: [{ value: "" }],
    framework: "",
  });
  // console.log(Object.(errors));
  // console.log(Object.keys(errors));
  const handleAddDistrict = () => {
    setData("name", [...data.name, { value: "" }]);
  };

  const handleRemoveDistrict = (index: number) => {
    setData(
      "name",
      data.name.filter((_, i) => i !== index)
    );
  };

  const handleDistrictChange = (index: number, value: string) => {
    const newName = [...data.name];
    newName[index].value = value;
    setData("name", newName);
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    post(route("permission.store"), {
      onSuccess: () => {
        toast.success("Area has been saved successfully");
        reset();
        onOpenChange?.(false);
      },
    });
  };

  return (
    <Sheet onOpenChange={onOpenChange} {...props}>
      <SheetContent>
        <SheetHeader>
          <SheetTitle>Create new Permissions</SheetTitle>
          <SheetDescription />
        </SheetHeader>

        <form className="grid gap-4 p-4" onSubmit={handleSubmit}>

          <div className="space-y-1">
            <Label>Framework</Label>
            <Select
              value={data.framework}
              onValueChange={(value) => setData("framework", value)}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Select Framework" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Framework</SelectLabel>
                  {frameworkItem.map((value: any, index: number) => (
                    <SelectItem key={index} value={value}>{value}</SelectItem>
                  ))}
                  {/* <SelectItem value="Application">Application</SelectItem>
                  <SelectItem value="Problem">Problem</SelectItem>
                  <SelectItem value="Permission">Permission</SelectItem> */}
                </SelectGroup>
              </SelectContent>
            </Select>
            <FormError error={errors.framework} />
          </div>

          <div className="space-y-1">
            <Label>Name</Label>
            {data.name.map((district, index) => (
              <div key={index}>
                <div className="flex items-center space-x-2">
                  <Input
                    value={district.value}
                    placeholder="Name"
                    onChange={(e) =>
                      handleDistrictChange(index, e.target.value)
                    }
                  />
                  {data.name.length > 1 && (
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-red-500 hover:text-red-600"
                      onClick={(e) => {
                        e.preventDefault();
                        handleRemoveDistrict(index);
                      }}
                    >
                      -
                    </Button>
                  )}
                  {index === data.name.length - 1 && (
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-green-500 hover:text-green-600"
                      onClick={handleAddDistrict}
                    >
                      +
                    </Button>
                  )}
                </div>

                <FormError error={errors[`name.${index}.value`]} />
              </div>
            ))}
          </div>

          <Button disabled={processing}>
            {processing && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
            {!processing ? "Save" : "Saving ..."}
          </Button>
        </form>
      </SheetContent>
    </Sheet>
  );
};

export default AddPermissionSheet;
