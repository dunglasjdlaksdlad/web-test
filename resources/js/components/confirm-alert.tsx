import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { AlertDialogProps } from '@radix-ui/react-alert-dialog';

type Props = AlertDialogProps & {
    message: string;
    title: string;
    onConfirm: () => void;
};

const ConFirmAlert = ({ onOpenChange, onConfirm, message, title, ...props }: Props) => {
    return (
        <AlertDialog onOpenChange={onOpenChange} {...props}>
            <AlertDialogContent className="top-[40%]">
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction className="bg-destructive hover:bg-destructive/80" onClick={() => onConfirm?.()}>
                        Continue
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
};

export default ConFirmAlert;
