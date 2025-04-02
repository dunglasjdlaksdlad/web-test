import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useForm } from '@inertiajs/react';
import { DialogProps } from '@radix-ui/react-dialog';
import { Loader2 } from 'lucide-react';
import { FormEvent } from 'react';
import { toast } from 'react-hot-toast';
import FormError from './form-error';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Label } from './ui/label';
type FormType = {
    name: string;
    districts: { value: string }[];
};

const AddAreaSheet = ({ onOpenChange, ...props }: DialogProps) => {
    const { data, setData, post, errors, reset, processing } = useForm<FormType>({
        name: '',
        districts: [{ value: '' }],
    });

    const handleAddDistrict = () => {
        setData('districts', [...data.districts, { value: '' }]);
    };

    const handleRemoveDistrict = (index: number) => {
        setData(
            'districts',
            data.districts.filter((_, i) => i !== index),
        );
    };

    const handleDistrictChange = (index: number, value: string) => {
        const newDistricts = [...data.districts];
        newDistricts[index].value = value;
        setData('districts', newDistricts);
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post(route('areas.store'), {
            onSuccess: () => {
                toast.success('Area has been saved successfully');
                reset();
                onOpenChange?.(false);
            },
        });
    };

    return (
        <Sheet onOpenChange={onOpenChange} {...props}>
            <SheetContent>
                <SheetHeader>
                    <SheetTitle>Create new Area/Districts</SheetTitle>
                    <SheetDescription />
                </SheetHeader>

                <form className="grid gap-4 p-4" onSubmit={handleSubmit}>
                    <div className="space-y-1">
                        <Label>Area</Label>
                        <Input value={data.name} placeholder="Area name" onChange={(e) => setData('name', e.target.value)} />
                        <FormError error={errors.name} />
                    </div>

                    <div className="space-y-1">
                        <Label>Districts</Label>
                        {data.districts.map((district, index) => (
                            <div key={index}>
                                <div className="flex items-center space-x-2">
                                    <Input
                                        value={district.value}
                                        placeholder="District name"
                                        onChange={(e) => handleDistrictChange(index, e.target.value)}
                                    />
                                    {data.districts.length > 1 && (
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
                                    {index === data.districts.length - 1 && (
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
                                <FormError error={errors?.[`districts.${index}.value`]} />
                            </div>
                        ))}
                    </div>

                    <Button disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        {!processing ? 'Save' : 'Saving ...'}
                    </Button>
                </form>
            </SheetContent>
        </Sheet>
    );
};

export default AddAreaSheet;
