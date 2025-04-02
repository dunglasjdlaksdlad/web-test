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
import { AArrowUpIcon, Loader2 } from "lucide-react";
import { FormEvent } from "react";
import { Toaster, toast } from "react-hot-toast";
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
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role: string;
  image: File | undefined;
};
const AddUserSheet = ({
  onOpenChange,
  roles,
  ...props
}: DialogProps & { roles: any }) => {
  const { data, setData, post, errors, reset, processing } = useForm<FormType>({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "",
    image: undefined,
  });
  // const aaa = roles.map((key: any) => {
  //   console.log(key);
  // });

  // console.log(roles);

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    // toast.success("User has been saved successfully");
    post(route("users.store"), {
      onSuccess: () => {
        toast.success("User has been saved successfully");
        reset();
        onOpenChange?.(false);
      },
    });
  };
  return (
    <Sheet onOpenChange={onOpenChange} {...props}>
      <SheetContent>
        <SheetHeader>
          <SheetTitle>Create new User</SheetTitle>
          <SheetDescription />
        </SheetHeader>

        <form className="grid gap-4 p-4" onSubmit={handleSubmit}>
          <div className="space-y-1">
            <Label>Full Name</Label>
            <Input
              value={data.name}
              autoComplete="username"
              onChange={(e) => setData("name", e.target.value)}
            />
            <FormError error={errors.name} />
          </div>

          <div className="space-y-1">
            <Label>Email Adrdress</Label>
            <Input
              value={data.email}
              type="email"
              autoComplete="email"
              onChange={(e) => setData("email", e.target.value)}
            />
            <FormError error={errors.email} />
          </div>

          <div className="space-y-1">
            <Label>Password</Label>
            <Input
              value={data.password}
              type="password"
              autoComplete="current-password"
              onChange={(e) => setData("password", e.target.value)}
            />
            <FormError error={errors.password} />
          </div>

          <div className="space-y-1">
            <Label>Confirm Password</Label>
            <Input
              value={data.password_confirmation}
              type="password"
              autoComplete="current-password"
              onChange={(e) => setData("password_confirmation", e.target.value)}
            />
            <FormError error={errors.password_confirmation} />
          </div>

          <div className="space-y-1">
            <Label>Role</Label>

            <Select
              value={data.role}
              onValueChange={(value) => setData("role", value)}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Select Role" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Select Role</SelectLabel>
                  {roles.map((key: any) => (
                    <SelectItem key={key.id} value={key.name}>
                      {key.name}
                    </SelectItem>
                  ))}
                  {/* <SelectItem value={"aa"}>aa</SelectItem> */}
                </SelectGroup>
              </SelectContent>
            </Select>
            <FormError error={errors.role} />
          </div>

          <div className="space-y-1">
            <Label>Profile Image</Label>
            <Input
              type="file"
              onChange={(e) => setData("image", e.target.files?.[0])}
            />
            <FormError error={errors.image} />
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

export default AddUserSheet;
